<?php

namespace App\Http\Controllers;

use App\Models\Activity;
use App\Models\Material;
use App\Models\Product;
use App\Models\User;
use App\Models\WeekActivity;
use App\Models\WeekPlanner;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class WeekActivityController extends Controller
{
    public function weeklyPlanner(Request $request)
    {
        DB::beginTransaction();

        try {
            $productId = $request->input('product_id');
            $weekData = $request->input('week');

            $product = Product::find($productId);
            if (!$product) {
                throw new \Exception("Producto con ID $productId no encontrado.");
            }

            // Obtenemos el lunes de la próxima semana
            $nextMonday = Carbon::now()->startOfWeek(Carbon::MONDAY)->addWeek();

            // Mapeo para calcular el offset de cada día
            $daysOfWeek = [
                'lunes' => 0,
                'martes' => 1,
                'miércoles' => 2,
                'jueves' => 3,
                'viernes' => 4,
                'sábado' => 5,
                'domingo' => 6,
            ];

            foreach ($weekData as $day => $data) {
                $activity = Activity::find($data['activity_id']);
                if (!$activity) {
                    throw new \Exception("Actividad con ID {$data['activity_id']} no encontrada.");
                }

                // Obtener el user_id del payload
                $userId = $data['user_id'] ?? null;
                if (!$userId) {
                    throw new \Exception("No se proporcionó un user_id para el día $day.");
                }

                $user = User::find($userId);
                if (!$user) {
                    throw new \Exception("Usuario con ID $userId no encontrado.");
                }

                // Validar que el usuario esté asociado a la actividad (usando activity_user)
                if (!$activity->users()->where('user_id', $userId)->exists()) {
                    throw new \Exception("El usuario con ID $userId no está asignado a la actividad con ID {$data['activity_id']}.");
                }

                $dayOffset = $daysOfWeek[$day] ?? 0;
                $activityDate = $nextMonday->copy()->addDays($dayOffset);

                $weekActivity = new WeekActivity();
                $weekActivity->description = $data['description'] ?? '';
                $weekActivity->date = $activityDate;
                $weekActivity->status = 'pending';
                $weekActivity->estimated_hours = $data['estimated_hours'] ?? '';
                $weekActivity->work_location = $data['work_location'] ?? '';
                $weekActivity->percentage = 0;
                $weekActivity->activity_id = $activity->id;
                $weekActivity->user_id = $userId; // Asociar al usuario enviado
                $weekActivity->save();

                // Asociar materiales (opcional)
                if (!empty($data['materials'])) {
                    foreach ($data['materials'] as $materialData) {
                        $materialId = $materialData['material_id'];
                        $materialDescription = $materialData['material_description'] ?? '';
                        $quantity = $materialData['quantity'] ?? 1;

                        $material = Material::find($materialId);
                        if (!$material) {
                            throw new \Exception("Material con ID {$materialId} no encontrado.");
                        }

                        $weekActivity->materials()->attach($materialId, [
                            'quantity' => $quantity,
                            'description' => $materialDescription
                        ]);
                    }
                }

                $planner = new WeekPlanner();
                $planner->product()->associate($product);
                $planner->weekActivity()->associate($weekActivity);
                $planner->save();
            }

            DB::commit();

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

            $activities = WeekActivity::with(['activity', 'activity.product'])
                ->whereBetween('date', [$lastMonday, $lastSunday])
                ->whereHas('activity.product', function ($query) use ($user) {
                    $query->where('user_id', $user->id);
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
                        'product_name' => $weekActivity->activity->product->name,
                        'activity_name' => $weekActivity->activity->description,
                        'status' => $weekActivity->status,
                        'percentage' => $weekActivity->percentage,
                        'observations' => $weekActivity->observations,
                    ];
                }),
            ]);
        } catch (\Exception $e) {
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

                // Actualizar porcentaje y observaciones
                $weekActivity->percentage = $progress['percentage'];
                $weekActivity->observations = $progress['observations'] ?? null;
                $weekActivity->save();

                // Actualizar execution_progress en la actividad relacionada
                $activity = $weekActivity->activity;
                $currentMonth = Carbon::now()->startOfMonth();

                $plannedProgress = $activity->monthlyProgress()->where('month', $currentMonth)->first();

                if (!$plannedProgress) {
                    throw new Exception("No se encontró un progreso mensual planificado para {$currentMonth->format('F Y')}.");
                }

                // Validar que el porcentaje semanal no exceda 100%
                if ($progress['percentage'] > 100) {
                    throw new Exception("El porcentaje semanal no puede exceder 100%.");
                }

                // Calcular porcentaje real a aplicar sobre el planificado
                $weeklyExecution = ($progress['percentage'] / 100) * $plannedProgress->percentage;

                // Validar que el nuevo porcentaje no exceda el planificado
                if ($weeklyExecution > $plannedProgress->percentage) {
                    throw new Exception("El progreso ejecutado excede el planificado para {$currentMonth->format('F Y')}.");
                }

                // Actualizar (o crear) execution_progress con el nuevo valor, sin sumar al existente
                $activity->executionProgress()->updateOrCreate(
                    ['month' => $currentMonth],
                    ['percentage' => $weeklyExecution]
                );
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
                    'detail' => 'Error al actualizar el progresso: ' . $e->getMessage(),
                    'code' => 500,
                ],
            ], 500);
        }
    }
}
