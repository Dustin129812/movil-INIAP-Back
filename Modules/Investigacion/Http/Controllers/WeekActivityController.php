<?php

namespace Modules\Investigacion\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Modules\Investigacion\Entities\Activity;
use Modules\Investigacion\Entities\Material;
use Modules\Investigacion\Entities\Performance_Indicator;
use Modules\Investigacion\Entities\WeekActivity;
use Modules\Investigacion\Entities\WeekPlanner;
use Modules\Investigacion\Notifications\OursWeekPlanner;
use Modules\Investigacion\Notifications\RateWeeklyActivityNo;


class WeekActivityController extends Controller
{

    public function weeklyPlanner(Request $request)
    {

        $today = Carbon::now();
        $baseMonday = null;

        if ($today->dayOfWeek >= Carbon::FRIDAY || $today->dayOfWeek == Carbon::SUNDAY) {
            $baseMonday = Carbon::now()->addWeek()->startOfWeek(Carbon::MONDAY);
        } else {
            $baseMonday = Carbon::now()->startOfWeek(Carbon::MONDAY);
        }

        DB::beginTransaction();

        try {
            $weeklyPlansData = $request->input('weeklyPlans');

            if (!is_array($weeklyPlansData)) {
                throw new \Exception("Formato de datos de planificación semanal inválido.");
            }

            $entries = [];

            foreach ($weeklyPlansData as $data) {
                $activityId = $data['activityId'];
                $description = $data['description'];
                $dayName = $data['day'];
                $materialsData = $data['materials'] ?? [];
                $selectedIndicators = $data['indicators'] ?? []; // Array de IDs de indicadores
                $observations = $data['observations'] ?? null;
                $selectedLogisticSupportUserIds = $data['logisticSupports'] ?? [];

                $activity = Activity::find($activityId);
                if (!$activity) {
                    throw new \Exception("Actividad con ID $activityId no encontrada.");
                }
                $product = $activity->product;

                $userId = Auth::id();
                if (!$userId) {
                    throw new \Exception("No se pudo determinar el usuario autenticado.");
                }

                $dayOffsets = [
                    'lunes' => 0,
                    'martes' => 1,
                    'miercoles' => 2,
                    'jueves' => 3,
                    'viernes' => 4,
                    'sábado' => 5,
                    'domingo' => 6,
                ];
                $nextMonday = Carbon::now()->startOfWeek(Carbon::MONDAY);
                $activityDate = $baseMonday->copy()->addDays($dayOffsets[$dayName] ?? 0);

                $weekActivity = new WeekActivity();
                $weekActivity->description = $description;
                $weekActivity->date = $activityDate;
                $weekActivity->status = 'pending';
                $weekActivity->work_location = $activity->work_location ?? 'Oficina'; // Asumo que work_location viene del Activity model. Si quieres el del frontend (activity.work_location), cámbialo.
                $weekActivity->observations = $observations;
                $weekActivity->percentage = 0;
                $weekActivity->activity_id = $activity->id;
                $weekActivity->user_id = $userId;
                $weekActivity->save();

                $entries[] = $weekActivity;

                if (!empty($materialsData)) {
                    $syncData = [];
                    foreach ($materialsData as $materialInput) {
                        $materialFromDb = Material::where('name', $materialInput['name'])->first(); // Busca el material por nombre
                        if ($materialFromDb) {
                            $syncData[$materialFromDb->id] = [
                                'quantity' => $materialInput['quantity'] ?? null,
                                'description' => $materialInput['description'] ?? null,
                                'created_at' => now(),
                                'updated_at' => now()
                            ];
                        }
                    }
                    if (!empty($syncData)) {
                        $weekActivity->materials()->sync($syncData);
                    }
                } else {
                    $weekActivity->materials()->detach(); // Si no se envían materiales, desvincula los existentes
                }

                if (!empty($selectedIndicators)) {
                    $syncIndicators = [];
                    foreach ($selectedIndicators as $indicatorId) {
                        $performanceIndicator = Performance_Indicator::find($indicatorId);
                        if (!$performanceIndicator) {
                            throw new \Exception("Indicador de rendimiento con ID {$indicatorId} no encontrado.");
                        }
                        $syncIndicators[$indicatorId] = [
                            'created_at' => now(),
                            'updated_at' => now()
                        ];
                    }
                    $weekActivity->performanceIndicators()->sync($syncIndicators);
                } else {
                    $weekActivity->performanceIndicators()->detach();
                }

                $validUserIds = array_filter($selectedLogisticSupportUserIds);
                if (!empty($validUserIds)) {
                    $syncData = [];
                    foreach ($validUserIds as $supportId) {
                        $syncData[$supportId] = ['status' => 'pending'];
                    }
                    $weekActivity->logisticSupportUsers()->sync($syncData);
                } else {
                    $weekActivity->logisticSupportUsers()->detach();
                }

                $planner = new WeekPlanner();
                $planner->product()->associate($activity->product);
                $planner->weekActivity()->associate($weekActivity);
                $planner->save();
            }

            DB::commit();

            $productManager = User::find($product->user_id);
            $updater = Auth::user();

            if ($productManager && $updater) { // Solo verifica que existan ambos
                $productManager->notify(
                    new OursWeekPlanner($entries, $updater)
                );
            }

            return response()->json([
                'message' => 'Planificación guardada correctamente.'
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function getPreviousWeekActivities(Request $request)
    {
        try {
            $user = $request->user();
            if (!$user) {
                return response()->json(['error' => 'Usuario no autenticado'], 401);
            }

            // Obtenemos el parámetro offset de la URL (si existe)
            $offset = $request->query('offset');

            // Construimos la consulta base que usan ambos modos
            $query = WeekActivity::with([
                'user',
                'activity.product',
                'activity.monthlyProgress',
                'activity.weeklyActivities',
                'logisticSupportUsers'
            ])->where(function ($q) use ($user) {
                $q->where('user_id', $user->id)
                    ->orWhereHas('logisticSupportUsers', function ($q2) use ($user) {
                        $q2->where('users.id', $user->id)
                            ->where('week_activity_logistic_support_user.status', '!=', 'rejected');
                    });
            });

            if ($offset !== null) {
                $targetDate = Carbon::now()->addWeeks((int)$offset);
                $startOfWeek = $targetDate->copy()->startOfWeek(Carbon::MONDAY);
                $endOfWeek = $targetDate->copy()->endOfWeek(Carbon::SUNDAY);

                $activities = $query->whereBetween('date', [$startOfWeek, $endOfWeek])
                    ->orderBy('date', 'asc')
                    ->get();
            } else {
                $targetSunday = Carbon::now()->endOfWeek(Carbon::SUNDAY);
                $ratedStatuses = ['not completed', 'completed', 'partial', 'rated'];

                $activities = $query->where('date', '<=', $targetSunday)
                    ->whereNotIn('status', $ratedStatuses)
                    ->orderBy('date', 'asc')
                    ->get();
            }

            return response()->json([
                'msg' => [
                    'summary' => 'Success',
                    'detail' => 'Actividades obtenidas correctamente',
                    'code' => 200,
                ],
                'data' => $activities->map(function ($weekActivity) use ($user) {
                    if (!$weekActivity->activity || !$weekActivity->activity->product) {
                        return null;
                    }
                    return [
                        'id' => $weekActivity->id,
                        'activity_id' => $weekActivity->activity->id,
                        'description' => $weekActivity->description,
                        'date' => Carbon::parse($weekActivity->date)->format('Y-m-d'),
                        'product_name' => $weekActivity->activity->product->name,
                        'activity_name' => $weekActivity->activity->description,
                        'status' => $weekActivity->status,
                        'percentage' => $weekActivity->percentage,
                        'observations' => $weekActivity->observations,

                        // Ahora $user sí existe aquí dentro
                        'is_owner' => $weekActivity->user_id === $user->id,
                        'owner_name' => $weekActivity->user ? $weekActivity->user->name : 'Compañero',
                        'my_support_status' => $weekActivity->user_id !== $user->id
                            ? $weekActivity->logisticSupportUsers->where('id', $user->id)->first()->pivot->status ?? 'pending'
                            : null,

                        // ✅ Cambiamos el nombre de la variable interna a $supportUser para evitar conflictos con $user
                        'logistic_supports' => $weekActivity->logisticSupportUsers->map(function ($supportUser) {
                            return ['id' => $supportUser->id, 'name' => $supportUser->name];
                        })->toArray(),

                        'monthly_plannig' => $weekActivity->activity->monthlyProgress->map(function ($progress) {
                            return [
                                'month' => Carbon::parse($progress->month)->format('Y-m-d'),
                                'percentage' => $progress->percentage,
                            ];
                        })->toArray(),

                        'execution_progress' => $weekActivity->activity->weeklyActivities->map(function ($exec) {
                            return [
                                'week_id' => $exec->id,
                                'date' => Carbon::parse($exec->date)->format('Y-m-d'),
                                'reported_percentage' => $exec->percentage, // El % que se reportó esa semana
                            ];
                        })->toArray(),
                    ];
                })->filter()->values(),
            ]);
        } catch (\Exception $e) {
            Log::error("Error al obtener actividades: " . $e->getMessage());
            return response()->json([
                'msg' => [
                    'summary' => 'Error',
                    'detail' => 'Error al obtener las actividades: ' . $e->getMessage(),
                    'code' => 500,
                ],
            ], 500);
        }
    }

    public function updateWeeklyProgress(Request $request)
    {
        $request->validate([
            'progress' => ['required', 'array'],
            'progress.*.week_activity_id' => ['required', 'exists:weekly_activities,id'],
            'progress.*.status' => ['required', 'string', 'in:yes,no,partial'],
            'progress.*.observations' => ['nullable', 'string'],
        ]);

        DB::beginTransaction();

        try {
            $investigador = Auth::user();

            foreach ($request->progress as $progressItem) {
                $weekActivity = WeekActivity::with(['activity.product.user'])
                    ->findOrFail($progressItem['week_activity_id']);

                // 1. Guardamos el estado anterior para validaciones
                $oldStatus = $weekActivity->status;

                $updatedStatus = $progressItem['status'];
                $observations = $progressItem['observations'] ?? null;

                $dbStatus = '';
                $numericPercentage = 0;

                switch ($updatedStatus) {
                    case 'yes':
                        $dbStatus = 'completed';
                        $numericPercentage = 100;
                        break;
                    case 'no':
                        $dbStatus = 'not completed';
                        $numericPercentage = 0;
                        break;
                    case 'partial':
                        $dbStatus = 'partial';
                        $numericPercentage = 50;
                        break;
                }

                $weekActivity->update([
                    'observations' => $observations,
                    'status' => $dbStatus,
                    'percentage' => $numericPercentage
                ]);

                // 2. OPTIMIZACIÓN UX: Solo notificar si NO estaba previamente en estado negativo
                // Esto evita el spam de correos si el usuario se equivocó y corrige la calificación al instante.
                $wasAlreadyNegative = in_array($oldStatus, ['not completed', 'partial']);

                if ($numericPercentage >= 0 && $numericPercentage < 100 && !$wasAlreadyNegative) {
                    $responsable = $weekActivity->activity?->product?->user;

                    if ($responsable && $responsable->id !== $investigador->id) {
                        $responsable->notify(
                            new RateWeeklyActivityNo(
                                $weekActivity->activity,
                                $investigador,
                                $numericPercentage,
                                $observations
                            )
                        );
                    }
                }
            }

            DB::commit();

            return response()->json([
                'msg' => [
                    'summary' => 'Success',
                    'detail' => 'Progreso de actividades actualizado correctamente',
                    'code' => 200,
                ],
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Error al actualizar el progreso de actividad: " . $e->getMessage(), [
                'exception' => $e,
                'request_data' => $request->all(),
                'user_id' => Auth::id()
            ]);

            return response()->json([
                'msg' => [
                    'summary' => 'Error',
                    'detail' => 'Error al actualizar el progreso: ' . $e->getMessage(),
                    'code' => 500,
                ],
            ], 500);
        }
    }

    public function registerPastWeek(Request $request)
    {
        $request->validate([
            'selected_date' => 'required|date_format:Y-m-d',
            'weeklyPlans' => 'required|array'
        ]);

        DB::beginTransaction();

        try {
            $weeklyPlansData = $request->input('weeklyPlans');

            if (!is_array($weeklyPlansData)) {
                throw new \Exception("Formato de datos de planificación semanal inválido.");
            }

            $selectedDate = $request->input('selected_date');

            $mondayOfSelectedWeek = Carbon::parse($selectedDate)->startOfWeek(Carbon::MONDAY);

            $entries = [];
            $product = null; // Para la notificación

            foreach ($weeklyPlansData as $data) {
                $activityId = $data['activityId'];
                $description = $data['description'];
                $dayName = $data['day'];
                $materialsData = $data['materials'] ?? [];
                $selectedIndicators = $data['indicators'] ?? []; // Array de IDs de indicadores
                $observations = $data['observations'] ?? null;
                $selectedLogisticSupportUserIds = $data['logisticSupports'] ?? [];

                $activity = Activity::find($activityId);
                if (!$activity) {
                    throw new \Exception("Actividad con ID $activityId no encontrada.");
                }

                // Guardamos el producto para la notificación (asumimos que todas son del mismo producto)
                if (!$product) {
                    $product = $activity->product;
                }

                $userId = Auth::id();
                if (!$userId) {
                    throw new \Exception("No se pudo determinar el usuario autenticado.");
                }

                $dayOffsets = [
                    'lunes' => 0,
                    'martes' => 1,
                    'miercoles' => 2,
                    'jueves' => 3,
                    'viernes' => 4,
                    'sábado' => 5,
                    'domingo' => 6,
                ];

                $activityDate = $mondayOfSelectedWeek->copy()->addDays($dayOffsets[$dayName] ?? 0);

                $weekActivity = new WeekActivity();
                $weekActivity->description = $description;
                $weekActivity->date = $activityDate;
                $weekActivity->status = 'pending'; // Inicia como pendiente, aunque sea pasada
                $weekActivity->work_location = $activity->work_location ?? 'Oficina';
                $weekActivity->observations = $observations;
                $weekActivity->percentage = 0;
                $weekActivity->activity_id = $activity->id;
                $weekActivity->user_id = $userId;
                $weekActivity->save();

                $entries[] = $weekActivity;

                if (!empty($materialsData)) {
                    $syncData = [];
                    foreach ($materialsData as $materialInput) {
                        $materialFromDb = Material::where('name', $materialInput['name'])->first();
                        if ($materialFromDb) {
                            $syncData[$materialFromDb->id] = [
                                'quantity' => $materialInput['quantity'] ?? null,
                                'description' => $materialInput['description'] ?? null,
                                'created_at' => now(),
                                'updated_at' => now()
                            ];
                        }
                    }
                    if (!empty($syncData)) {
                        $weekActivity->materials()->sync($syncData);
                    }
                } else {
                    $weekActivity->materials()->detach();
                }

                if (!empty($selectedIndicators)) {
                    $syncIndicators = [];
                    foreach ($selectedIndicators as $indicatorId) {
                        $performanceIndicator = Performance_Indicator::find($indicatorId);
                        if (!$performanceIndicator) {
                            throw new \Exception("Indicador de rendimiento con ID {$indicatorId} no encontrado.");
                        }
                        $syncIndicators[$indicatorId] = [
                            'created_at' => now(),
                            'updated_at' => now()
                        ];
                    }
                    $weekActivity->performanceIndicators()->sync($syncIndicators);
                } else {
                    $weekActivity->performanceIndicators()->detach();
                }

                $validUserIds = array_filter($selectedLogisticSupportUserIds);
                if (!empty($validUserIds)) {
                    $weekActivity->logisticSupportUsers()->sync($validUserIds);
                } else {
                    $weekActivity->logisticSupportUsers()->detach();
                }

                $planner = new WeekPlanner();
                $planner->product()->associate($activity->product);
                $planner->weekActivity()->associate($weekActivity);
                $planner->save();
            }

            DB::commit();

            if ($product) {
                $productManager = User::find($product->user_id);
                $updater = Auth::user();

                if ($productManager && $updater) {
                    $productManager->notify(
                        new OursWeekPlanner($entries, $updater)
                    );
                }
            }

            return response()->json([
                'message' => 'Planificación de semana pasada guardada correctamente.'
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            if ($e instanceof \Illuminate\Validation\ValidationException) {
                return response()->json(['error' => $e->errors()], 422);
            }
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function respondToSupport(Request $request, $activityId)
    {
        $request->validate([
            'status' => 'required|in:accepted,rejected'
        ]);

        $user = Auth::user();
        $activity = WeekActivity::findOrFail($activityId);

        $activity->logisticSupportUsers()->updateExistingPivot($user->id, [
            'status' => $request->status
        ]);

        return response()->json(['message' => 'Respuesta registrada correctamente.']);
    }
}
