<?php

namespace App\Http\Controllers;

use App\Models\Activity;
use App\Models\LogisticSupport;
use App\Models\Material;
use App\Models\Performance_Indicator;
use App\Models\Product;
use App\Models\User;
use App\Models\WeekActivity;
use App\Models\WeeklyIndicators;
use App\Models\WeekPlanner;
use App\Notifications\CreateProduct;
use App\Notifications\CreateWeekPlanner;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class WeekActivityController extends Controller
{

    public function weeklyPlanner(Request $request)
    {
        DB::beginTransaction();

        try {
            $weeklyPlansData = $request->input('weeklyPlans');

            if (!is_array($weeklyPlansData)) {
                throw new \Exception("Formato de datos de planificación semanal inválido.");
            }

            foreach ($weeklyPlansData as $data) {
                $activityId = $data['activityId'];
                $dayName = $data['day'];
                $estimatedHours = $data['hours'];
                $materialsData = $data['materials'] ?? []; // Cambiado a materialsData para diferenciar
                $selectedIndicators = $data['indicators'] ?? []; // Array de IDs de indicadores
                $observations = $data['observations'] ?? null;
                // Ahora $selectedLogisticSupports contendrá un array de IDs de usuario
                $selectedLogisticSupportUserIds = $data['logisticSupports'] ?? [];


                $activity = Activity::find($activityId);
                if (!$activity) {
                    throw new \Exception("Actividad con ID $activityId no encontrada.");
                }
                // Obtener el producto asociado a la actividad
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
                $activityDate = $nextMonday->copy()->addDays($dayOffsets[$dayName] ?? 0);

                $weekActivity = new WeekActivity();
                $weekActivity->description = $activity->description; // Esto toma la descripción de la actividad base. El front también envía 'description' en el activity object, ¿debería ser ese? Asumo que quieres la del Activity model.
                $weekActivity->date = $activityDate;
                $isExtraPoa = $activity->product->name === 'Actividades Extra POA';
                $weekActivity->status = $isExtraPoa ? 'approved' : 'pending';
                $weekActivity->estimated_hours = $estimatedHours;
                $weekActivity->work_location = $activity->work_location ?? 'Oficina'; // Asumo que work_location viene del Activity model. Si quieres el del frontend (activity.work_location), cámbialo.
                $weekActivity->observations = $observations;
                $weekActivity->percentage = 0;
                $weekActivity->activity_id = $activity->id;
                $weekActivity->user_id = $userId;
                $weekActivity->save();

                if (!empty($materialsData)){
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

                // Manejar los usuarios de soporte logístico
                if (!empty($selectedLogisticSupportUserIds)) {
                    // Sincroniza los usuarios directamente.
                    // Asegúrate de que tu modelo WeekActivity tenga una relación many-to-many
                    // llamada 'logisticSupportUsers' o similar que apunte al modelo User.
                    // No es necesario buscar cada usuario si ya vienen como IDs válidos.
                    $weekActivity->logisticSupportUsers()->sync($selectedLogisticSupportUserIds);
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

            if ($productManager && $updater && $productManager->id !== $updater->id) {
                $productManager->notify(new CreateWeekPlanner($product, $updater));
            }

            return response()->json(['message' => 'Planificación guardada correctamente.']);
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

            $lastMonday = Carbon::now()->subWeek()->startOfWeek(Carbon::MONDAY);
            $lastSunday = $lastMonday->copy()->endOfWeek(Carbon::SUNDAY);

            $activities = WeekActivity::with([
                'activity',
                'activity.product',
                'logisticSupportUsers' // Carga la relación de usuarios de soporte logístico
            ])
                ->whereBetween('date', [$lastMonday, $lastSunday])
                ->where('user_id', $user->id)
                ->where(function ($query) {
                    $query->whereNull('percentage')
                        ->orWhere('percentage', 0); // <-- Si usas 0 como no evaluado
                })
                ->get();

            return response()->json([
                'msg' => [
                    'summary' => 'Success',
                    'detail' => 'Actividades de la semana anterior obtenidas correctamente',
                    'code' => 200,
                ],
                'data' => $activities->map(function ($weekActivity) {
                    return [
                        'id' => $weekActivity->id,
                        'activity_id' => $weekActivity->activity->id,
                        'description' => $weekActivity->description,
                        'date' => \Carbon\Carbon::parse($weekActivity->date)->format('Y-m-d'),
                        'product_name' => $weekActivity->activity->product ? $weekActivity->activity->product->name : $weekActivity->product_name,
                        'activity_name' => $weekActivity->activity->description,
                        'status' => $weekActivity->status,
                        'percentage' => $weekActivity->percentage,
                        'observations' => $weekActivity->observations,
                        // Mapea los usuarios de soporte logístico para incluirlos en la respuesta
                        'logistic_supports' => $weekActivity->logisticSupportUsers->map(function ($user) {
                            return [
                                'id' => $user->id,
                                'name' => $user->name,
                            ];
                        })->toArray(),
                    ];
                }),
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
        'progress.*.percentage' => ['required', 'numeric', 'min:0', 'max:100'],
        'progress.*.observations' => ['nullable', 'string'],
    ]);

    DB::beginTransaction();

    try {
        foreach ($request->progress as $progress) {
            $weekActivity = WeekActivity::findOrFail($progress['week_activity_id']);

            $weekActivity->update([
                'percentage' => $progress['percentage'],
                'observations' => $progress['observations'] ?? null,
            ]);
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

        return response()->json([
            'msg' => [
                'summary' => 'Error',
                'detail' => 'Error al actualizar el progreso: ' . $e->getMessage(),
                'code' => 500,
            ],
        ], 500);
    }
}

}
