<?php

namespace App\Http\Controllers;

use App\Models\Activity;
use App\Models\Material;
use App\Models\Product;
use App\Notifications\CreateActivity;
use App\Notifications\CreateProduct;
use App\Notifications\CreateWeekPlanner;
use App\Notifications\PlannerAccept;
use App\Notifications\ProductUpdated;
use Illuminate\Support\Facades\Auth;
use App\Models\User;
use App\Models\WeekActivity;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Notifications\Notifiable;

class PlannerController extends Controller
{
    public function addProductAndActivity(Request $request)
    {
        // Iniciar una transacción para garantizar la integridad de los datos
        DB::beginTransaction();

        try {
            $userLocation = auth()->user()->location;

            // --- Crear el Producto ---
            $product = new Product();
            $product->name = $request->input('name');
            $product->budget = $request->input('budget');
            $product->ponderacion = $request->input('ponderacion');
            $product->user_id = $request->input('user');
            $product->rubro_id = $request->input('rubro');
            $product->location_id = $userLocation->id;
            $product->save();

            // Asignar rol al responsable del producto
            $user = User::find($request->input('user'));
            if ($user) {
                $user->assignRole('product-manager');
            }

            // --- Procesar Actividades ---
            foreach ($request->input('activities', []) as $activityData) {
                // Crear la actividad
                $activity = new Activity();
                $activity->description = $activityData['description'];
                $activity->budget = $activityData['budget'];
                $activity->ponderacion = $activityData['ponderacion'];
                $activity->start_date = $activityData['start_date'];
                $activity->end_date = $activityData['end_date'];
                $activity->product_id = $product->id;
                $activity->save();

                // Sincronizar los usuarios responsables (acepta un array)
                if (!empty($activityData['user'])) {
                    $activity->users()->sync($activityData['user']);
                }

                // Sincronizar los indicadores (acepta un array)
                // Se asegura de usar la clave 'indicators' (plural) que viene del frontend
                if (!empty($activityData['indicators'])) {
                    $activity->indicators()->sync($activityData['indicators']);
                }

                // Asignar rol 'researcher' a cada responsable de la actividad
                foreach ($activityData['user'] as $userId) {
                    $responsible = User::find($userId);
                    if ($responsible) {
                        $responsible->assignRole('researcher');
                        $responsible->notify(new \App\Notifications\CreateActivity($activity, auth()->user()));
                    }
                }

                // Crear la distribución mensual
                foreach ($activityData['monthly_distribution'] as $monthData) {
                    $activity->monthlyProgress()->create([
                        'month' => \Carbon\Carbon::parse($monthData['month'])->startOfMonth(),
                        'percentage' => $monthData['percentage'],
                    ]);
                }
            }

            DB::commit();

            $productManager = User::find($product->user_id);
            $updater = Auth::user();

            if ($productManager && $updater && $productManager->id !== $updater->id) {
                $productManager->notify(new CreateProduct($product, $updater));
            }


            return response()->json([
                'msg' => [
                    'summary' => 'Éxito',
                    'detail' => 'El producto y sus actividades han sido guardados correctamente',
                    'code' => 201,
                ],
            ], 201);
        } catch (Exception $e) {
            // Si algo falla, revertir todos los cambios
            DB::rollBack();
            return response()->json([
                'msg' => [
                    'summary' => 'Error',
                    'detail' => 'Error al guardar el producto y actividades: ' . $e->getMessage(),
                    'code' => 500,
                ],
            ], 500);
        }
    }

    public function updateProductAndActivity(Request $request, $id)
    {
        DB::beginTransaction();

        try {
            $product = Product::findOrFail($id);
            $product->update($request->only([
                'name',
                'budget',
                'ponderacion',
                'user_id',
                'rubro_id',
            ]));

            // Asignar rol al responsable del producto
            if ($request->has('user_id')) {
                $user = User::find($request->input('user_id'));
                if ($user) {
                    $user->assignRole('product-manager');
                }
            }

            $receivedActivityIds = [];

            foreach ($request->input('activities', []) as $activityData) {
                // Busca una actividad existente o crea una nueva si el ID es nulo
                $activity = Activity::findOrNew($activityData['id'] ?? null);

                // Asigna los valores a la actividad
                $activity->description = $activityData['description'];
                $activity->budget = $activityData['budget'];
                $activity->ponderacion = $activityData['ponderacion'];
                $activity->start_date = $activityData['start_date'];
                $activity->end_date = $activityData['end_date'];
                $activity->product_id = $product->id;
                $activity->save();

                // CORRECCIÓN 2: El frontend ya envía arrays de IDs, por lo que no es necesario usar pluck().
                $userIds = $activityData['user'] ?? [];
                $indicatorIds = $activityData['indicators'] ?? [];

                $activity->users()->sync($userIds);
                $activity->indicators()->sync($indicatorIds);

                // Asignar rol 'researcher' a cada responsable
                foreach ($userIds as $userId) {
                    $responsible = User::find($userId);
                    if ($responsible) {
                        $responsible->assignRole('researcher');
                    }
                }

                // Borrar y recrear el progreso mensual para evitar duplicados
                $activity->monthlyProgress()->delete();
                if (!empty($activityData['monthly_distribution'])) {
                    foreach ($activityData['monthly_distribution'] as $monthData) {
                        $activity->monthlyProgress()->create([
                            'month' => \Carbon\Carbon::parse($monthData['month'])->startOfMonth(),
                            'percentage' => $monthData['percentage'],
                        ]);
                    }
                }

                $receivedActivityIds[] = $activity->id;
            }

            // Eliminar actividades que ya no forman parte del producto
            $product->activities()->whereNotIn('id', $receivedActivityIds)->delete();

            DB::commit();

            $productManager = User::find($product->user_id);
            $updater = Auth::user();

            if ($productManager && $updater && $productManager->id !== $updater->id) {
                $productManager->notify(new ProductUpdated($product, $updater));
            }

            return response()->json([
                'msg' => [
                    'summary' => 'Actualización Exitosa',
                    'detail' => 'El producto y sus actividades han sido actualizados correctamente',
                    'code' => 200,
                ],
            ], 200);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'msg' => [
                    'summary' => 'Error',
                    'detail' => 'Error al actualizar el producto y actividades: ' . $e->getMessage(),
                    'code' => 500,
                ],
            ], 500);
        }
    }

    public function getMaterial()
    {
        $materials = Material::all();

        return response()->json($materials);
    }

    public function approveActivity(Request $request, $activityId)
    {
        if (!auth()->user()->hasRole('product-manager')) {
            return response()->json(['error' => 'No autorizado.'], 403);
        }

        $weekActivity = WeekActivity::findOrFail($activityId);

        $status = $request->input('status');
        $validStatuses = ['approved', 'rejected'];

        if (!in_array($status, $validStatuses)) {
            return response()->json(['error' => 'Estado inválido. Use "approved" o "rejected".'], 400);
        }

        // Actualiza y guarda el estado
        $weekActivity->status = $status;
        if (!$weekActivity->save()) {
            Log::error(" Error al guardar el estado '{$status}' para la actividad ID {$activityId}");
            return response()->json(['error' => 'No se pudo actualizar la actividad.'], 500);
        }

        Log::info("Actividad ID {$activityId} actualizada con estado '{$status}' por usuario ID " . auth()->id());

        $creator = $weekActivity->user;
        $approver = auth()->user();

        if ($creator && $approver && $creator->id !== $approver->id) {
            $creator->notify(new PlannerAccept($weekActivity, $approver, $status));
            Log::info("Notificación enviada al creador ID {$creator->id} para la actividad ID {$activityId} con estado '{$status}'");
        }

        return response()->json([
            'message' => 'Actividad actualizada correctamente.',
            'activity_id' => $activityId,
            'status' => $status,
        ]);
    }

    public function getWeeklyPlanningByResponsible()
    {
        // 1. Empezamos por los usuarios que SÍ han creado al menos una actividad semanal.
        $usersWithPlans = User::whereHas('createdWeekActivities')
            ->with([
                // 2. Cargamos esas actividades semanales y sus relaciones anidadas.
                'createdWeekActivities' => function ($query) {
                    $query->with([
                        // Para cada actividad semanal, necesitamos la actividad principal y su producto.
                        'activity' => function ($activityQuery) {
                            $activityQuery->select('id', 'description', 'product_id')
                                ->with('product:id,name');
                        },
                        // También cargamos los materiales de la actividad semanal.
                        'materials'
                    ]);
                }
            ])
            ->get();

        // 3. Transformamos los datos para la respuesta JSON.
        $formattedResult = $usersWithPlans->map(function ($user) {
            return [
                'id' => $user->id,
                'name' => $user->name,
                // 4. Agrupamos las actividades semanales del usuario por la actividad principal a la que pertenecen.
                'activities' => $user->createdWeekActivities->groupBy('activity_id')->map(function ($weekActivitiesGroup) {

                    // La información de la actividad principal es la misma para todo el grupo.
                    $firstWeekActivity = $weekActivitiesGroup->first();
                    $mainActivity = $firstWeekActivity->activity;

                    return [
                        'product_name' => $mainActivity->product->name ?? null,
                        'product_id' => $mainActivity->product->id ?? null,
                        'activity_description' => $mainActivity->description ?? null,
                        // Mapeamos cada actividad semanal dentro de este grupo.
                        'week_activities' => $weekActivitiesGroup->map(fn($wa) => [
                            'id' => $wa->id,
                            'week_description' => $wa->description,
                            'date' => $wa->date,
                            'day_of_week' => \Carbon\Carbon::parse($wa->date)->format('l (d/m/Y)'),
                            'materials' => $wa->materials,
                            'status' => $wa->status,
                        ])->values(),
                    ];
                })->values(), // Usamos values() para resetear las claves y obtener un array.
            ];
        });

        return response()->json(['data' => $formattedResult]);
    }


    public function getProductsWithActivities(Request $request)
    {
        try {
            $user = $request->user();

            $products = Product::with([
                'location',
                'rubro',
                'user', // Responsable del producto
                'activities' => function ($query) {
                    $query->with(['users', 'indicators', 'monthlyProgress', 'executionProgress']);
                },
            ])->get();

            // Filtrar el producto "Actividades Extra POA"
            $products = $products->filter(function ($product) {
                return $product->name !== 'Actividades Extra POA';
            });

            // Mapear los datos
            $formattedProducts = $products->map(function ($product) {
                return [
                    'id' => $product->id,
                    'name' => $product->name,
                    'budget' => $product->budget,
                    'ponderacion' => $product->ponderacion,
                    'user' => $product->user ? [
                        'id' => $product->user->id,
                        'name' => $product->user->name ?? 'Sin nombre',
                    ] : null,
                    'location' => $product->location ? [
                        'id' => $product->location->id,
                        'name' => $product->location->name,
                    ] : null,
                    'rubro' => $product->rubro ? [
                        'id' => $product->rubro->id,
                        'name' => $product->rubro->name,
                    ] : null,
                    'activity' => ($product->activities ?? collect([]))->map(function ($activity) {
                        return [
                            'id' => $activity->id,
                            'description' => $activity->description,
                            'budget' => $activity->budget,
                            'ponderacion' => $activity->ponderacion,
                            'start_date' => $activity->start_date ? Carbon::parse($activity->start_date)->format('Y-m-d') : null,
                            'end_date'   => $activity->end_date ? Carbon::parse($activity->end_date)->format('Y-m-d') : null,
                            'user' => ($activity->users ?? collect([]))->map(function ($user) {
                                return [
                                    'id' => $user->id,
                                    'name' => $user->name ?? 'Sin nombre',
                                ];
                            })->toArray(),

                            // Se mapea la colección de indicadores, igual que se hace con los usuarios.
                            'indicators' => ($activity->indicators ?? collect([]))->map(function ($indicators) {
                                return [
                                    'id' => $indicators->id,
                                    'name' => $indicators->name,
                                ];
                            })->toArray(),

                            'monthly_progress' => ($activity->monthlyProgress ?? collect([]))->map(function ($progress) {
                                return [
                                    'month' => Carbon::parse($progress->month)->format('Y-m-d'),
                                    'percentage' => $progress->percentage,
                                ];
                            })->toArray(),
                            'execution_progress' => ($activity->executionProgress ?? collect([]))->map(function ($progress) {
                                return [
                                    'month' => Carbon::parse($progress->month)->format('Y-m-d'),
                                    'percentage' => $progress->percentage,
                                ];
                            })->toArray(),
                        ];
                    })->toArray(),
                ];
            })->values()->toArray(); // Usamos values() para re-indexar el array después de filter()

            return response()->json([
                'msg' => [
                    'summary' => 'Success',
                    'detail' => 'Productos obtenidos correctamente',
                    'code' => 200,
                ],
                'data' => $formattedProducts,
            ]);
        } catch (Exception $e) {
            return response()->json([
                'msg' => [
                    'summary' => 'Error',
                    'detail' => 'Error al obtener los productos: ' . $e->getMessage(),
                    'code' => 500,
                ],
            ], 500);
        }
    }
   public function getUserAssociatedCounts(Request $request)
    {
        try {
            $userId = $request->user()->id;

            // Conteo de productos asociados (ya existente)
            $totalAssociatedProducts = Product::whereUserRelated($userId)->count();

            // Conteo de actividades donde el usuario es responsable (ya existente)
            $activitiesAsResearcherCount = Activity::whereHas('users', function ($query) use ($userId) {
                $query->where('users.id', $userId);
            })->count();

            // --- Nuevas métricas ---

            // 1. Conteo de actividades completadas (con 100% de progreso en el último registro de ejecución)
            $completedActivitiesCount = 0;
            $userActivitiesWithProgress = Activity::whereHas('users', function ($query) use ($userId) {
                $query->where('users.id', $userId);
            })->with(['executionProgress' => function($query) {
                $query->orderBy('month', 'desc'); // Asegura que el progreso más reciente esté al principio
            }])->get();

            foreach ($userActivitiesWithProgress as $activity) {
                $latestExecution = $activity->executionProgress->first(); // Obtiene el último registro
                if ($latestExecution && $latestExecution->percentage === 100) {
                    $completedActivitiesCount++;
                }
            }

            // 2. Meta de progreso mensual y progreso actual para el mes en curso
            $currentMonth = Carbon::now()->startOfMonth();
            $totalPlannedPercentage = 0;
            $totalExecutedPercentage = 0;
            $activitiesCountForMonthlyMetrics = 0; // Para calcular el promedio

            $userActivitiesForMonthlyProgress = Activity::whereHas('users', function ($query) use ($userId) {
                $query->where('users.id', $userId);
            })
            ->with([
                'monthlyProgress' => function ($query) use ($currentMonth) {
                    $query->where('month', $currentMonth);
                },
                'executionProgress' => function ($query) use ($currentMonth) {
                    $query->where('month', $currentMonth);
                }
            ])->get();

            foreach ($userActivitiesForMonthlyProgress as $activity) {
                $planned = $activity->monthlyProgress->first();
                $executed = $activity->executionProgress->first();

                // Suma el porcentaje si existe un registro para el mes actual
                if ($planned) {
                    $totalPlannedPercentage += $planned->percentage;
                    $activitiesCountForMonthlyMetrics++; // Cuenta solo las actividades que tienen un plan para el mes
                }
                if ($executed) {
                    $totalExecutedPercentage += $executed->percentage;
                    // Si una actividad tiene ejecución pero no planificado para el mes, también la contamos para el promedio
                    if (!$planned) { // Evita duplicar el conteo si ya se sumó por 'planned'
                        $activitiesCountForMonthlyMetrics++;
                    }
                }
            }

            // Calcula los promedios, evitando división por cero
            $userPlannedMonthlyAverageProgress = $activitiesCountForMonthlyMetrics > 0 ?
                round($totalPlannedPercentage / $activitiesCountForMonthlyMetrics, 2) : 0;
            $userActualMonthlyAverageProgress = $activitiesCountForMonthlyMetrics > 0 ?
                round($totalExecutedPercentage / $activitiesCountForMonthlyMetrics, 2) : 0;


            return response()->json([
                'msg' => [
                    'summary' => 'Éxito',
                    'detail' => 'Conteo de asociaciones y progreso obtenido correctamente',
                    'code' => 200,
                ],
                'data' => [
                    'total_associated_products' => $totalAssociatedProducts,
                    'activities_as_researcher' => $activitiesAsResearcherCount,
                    'completed_activities_count' => $completedActivitiesCount,
                    'user_planned_monthly_average_progress' => $userPlannedMonthlyAverageProgress,
                    'user_actual_monthly_average_progress' => $userActualMonthlyAverageProgress,
                ],
            ]);
        } catch (Exception $e) {
            return response()->json([
                'msg' => [
                    'summary' => 'Error',
                    'detail' => 'Error al obtener el conteo de asociaciones y progreso: ' . $e->getMessage(),
                    'code' => 500,
                ],
            ], 500);
        }
    }

}
