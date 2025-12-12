<?php

namespace App\Modules\Planificacion\Http\Controllers;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Modules\Planificacion\Models\Activity;
use App\Modules\Planificacion\Models\ActivityExecutionProgress;
use App\Modules\Planificacion\Models\Location;
use App\Modules\Planificacion\Models\Product;
use App\Modules\Planificacion\Models\Rubro;
use App\Modules\Planificacion\Models\WeekActivity;
use App\Modules\Planificacion\Notifications\CreateProduct;
use App\Modules\Planificacion\Notifications\PlannerAccept;
use App\Modules\Planificacion\Notifications\ProductUpdated;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PlannerController extends Controller
{
    public function addProductAndActivity(Request $request)
    {
        DB::beginTransaction();

        try {
            $userLocation = auth()->user()->location;

            $product = new Product();
            $product->name = $request->input('name');
            $product->budget = $request->input('budget');
            $product->budget_types_id= $request->input('budget_types_id'); //presupuesto update
            $product->ponderacion = $request->input('ponderacion');
            $product->user_id = $request->input('user');
            $product->rubro_id = $request->input('rubro');
            $product->location_id = $userLocation->id;
            $product->save();

            $user = User::find($request->input('user'));
            if ($user) {
                $user->assignRole('product-manager');
            }

            foreach ($request->input('activities', []) as $activityData) {
                $activity = new Activity();
                $activity->description = $activityData['description'];
                $activity->budget = $activityData['budget'];
                $activity->accrued_budget= $activityData['accrued_budget']; //presupuesto update
                $activity->ponderacion = $activityData['ponderacion'];
                $activity->start_date = $activityData['start_date'];
                $activity->end_date = $activityData['end_date'];
                $activity->product_id = $product->id;
                $activity->save();

                if (!empty($activityData['user'])) {
                    $activity->users()->sync($activityData['user']);
                }

                if (!empty($activityData['indicators'])) {
                    $activity->indicators()->sync($activityData['indicators']);
                }

                foreach ($activityData['user'] as $userId) {
                    $responsible = User::find($userId);
                    if ($responsible) {
                        $responsible->assignRole('researcher');
                        $responsible->notify(new \App\Modules\Planificacion\Notifications\CreateActivity($activity, auth()->user()));
                    }
                }

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

            if ($request->has('user_id')) {
                $user = User::find($request->input('user_id'));
                if ($user) {
                    $user->assignRole('product-manager');
                }
            }

            $receivedActivityIds = [];

            foreach ($request->input('activities', []) as $activityData) {
                $activity = Activity::findOrNew($activityData['id'] ?? null);

                $activity->description = $activityData['description'];
                $activity->budget = $activityData['budget'];
                $activity->ponderacion = $activityData['ponderacion'];
                $activity->start_date = $activityData['start_date'];
                $activity->end_date = $activityData['end_date'];
                $activity->product_id = $product->id;
                $activity->save();

                $userIds = $activityData['user'] ?? [];
                $indicatorIds = $activityData['indicators'] ?? [];

                $activity->users()->sync($userIds);
                $activity->indicators()->sync($indicatorIds);

                foreach ($userIds as $userId) {
                    $responsible = User::find($userId);
                    if ($responsible) {
                        $responsible->assignRole('researcher');
                    }
                }

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

    public function approveActivity(Request $request, $activityId)
    {
        if (!auth()->user()->hasRole('product-manager')) {
            return response()->json(['error' => 'No autorizado.'], 403);
        }

        $weekActivity = WeekActivity::findOrFail($activityId);

        $status = $request->input('status');
        $validStatuses = ['approved', 'rejected', 'reassigned'];

        if (!in_array($status, $validStatuses)) {
            return response()->json(['error' => 'El estado proporcionado no es válido.'], 400);
        }

        $weekActivity->status = $status;
        if (!$weekActivity->save()) {
            Log::error(" Error al guardar el estado '{$status}' para la actividad ID {$activityId}");
            return response()->json(['error' => 'No se pudo actualizar la actividad.'], 500);
        }

        $creator = $weekActivity->user;
        $approver = auth()->user();

        if ($creator && $approver && $creator->id !== $approver->id) {
            $creator->notify(new PlannerAccept($weekActivity, $approver, $status));
        }

        return response()->json([
            'message' => 'Actividad actualizada correctamente.',
            'activity_id' => $activityId,
            'status' => $status,
        ]);
    }

    public function getWeeklyPlanningByResponsible(Request $request)
    {
        try {
            $revisor = $request->user();
            $revisor->load('groups.members');

            $teamMemberIds = $revisor->groups->flatMap(function ($group) {
                return $group->members->pluck('id');
            })->unique();

            if ($teamMemberIds->isEmpty()) {
                return response()->json(['data' => []]);
            }

            $relevantStatuses = ['pending', 'approved', 'rejected', 'reassigned'];

            $allPendingActivities = WeekActivity::whereIn('status', $relevantStatuses)
                ->whereIn('user_id', $teamMemberIds)
                ->with([
                    'activity.product.rubro',
                    'activity.product.location',
                    'user',
                    'materials',
                    'activity.indicators',
                    'logisticSupports'
                ])
                ->get();

            $groupedByUser = [];
            foreach ($allPendingActivities as $weekActivity) {
                // Se asegura de no procesar datos incompletos
                if (!$weekActivity->user || !$weekActivity->activity || !$weekActivity->activity->product) {
                    continue;
                }
                $groupedByUser[$weekActivity->user_id][] = $weekActivity;
            }

            $userIds = array_keys($groupedByUser);
            $users = User::whereIn('id', $userIds)->get()->keyBy('id');

            $formattedData = [];
            foreach ($groupedByUser as $userId => $activities) {
                if (!isset($users[$userId])) continue;

                $user = $users[$userId];
                $groupedByProduct = collect($activities)->groupBy('activity.product_id');

                $formattedData[] = [
                    'id' => $user->id,
                    'name' => $user->name,
                    'activities' => $groupedByProduct->map(function ($activitiesForProduct) {
                        $firstActivity = $activitiesForProduct->first();
                        return [
                            'product_id' => $firstActivity->activity->product->id,
                            'product_name' => $firstActivity->activity->product->name,
                            'activity_description' => $firstActivity->activity->name,
                            'week_activities' => $activitiesForProduct->values(),
                        ];
                    })->values()->toArray()
                ];
            }

            return response()->json(['data' => $formattedData]);

        } catch (Exception $e) {
            Log::error('Error en getWeeklyPlanningByResponsible: ' . $e->getMessage(), [
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);
            return response()->json([
                'msg' => ['summary' => 'Error', 'detail' => 'No se pudieron cargar las planificaciones para revisión.', 'code' => 500]
            ], 500);
        }
    }

    public function getProductsWithActivities(Request $request)
    {
        try {
            $user = $request->user();

            $products = Product::where('location_id', $user->location_id)
                ->whereHas('rubro', function ($query) {
                    $query->where('name', '!=', 'OFICIAL');
                })
                ->with([
                    'location',
                    'rubro',
                    'user',
                    'budget_type',
                    'activities' => function ($query) {
                        $query->with(['users', 'indicators', 'monthlyProgress', 'weeklyActivities', 'monthlyExecutionProgress']);
                    },
                ])->get();

            // Mapear los datos y añadir los cálculos de ponderación
            $formattedProducts = $products->map(function ($product) {

                $productAbsoluteWeight = (float) $product->ponderacion / 100;

                $mappedActivities = ($product->activities ?? collect([]))->map(function ($activity) use ($productAbsoluteWeight) {

                    $activityAbsoluteWeight = $productAbsoluteWeight * ((float) $activity->ponderacion / 100);

                    $activity->loadMissing('monthlyExecutionProgress');

                    $totalExecutedPercentage = $activity->monthlyExecutionProgress->sum('percentage');

                    $totalActivityProgress = $activityAbsoluteWeight * ($totalExecutedPercentage / 100);

                    $executionProgress = ($activity->monthlyExecutionProgress ?? collect([]))->map(function ($execProgress) {
                        return [
                            'month' => Carbon::parse($execProgress->month)->format('Y-m-d'),
                            'reported_percentage' => (float) $execProgress->percentage,
                        ];
                    })->toArray();

                    return [
                        'id' => $activity->id,
                        'description' => $activity->description,
                        'absolute_weight' => $activityAbsoluteWeight,
                        'budget'=>$activity->budget,
                        'accrued_budget'=> $activity->accrued_budget,
                        'total_progress' => $totalActivityProgress, // Este es el valor clave actualizado.
                        'total_completion_percentage' => $totalExecutedPercentage,// Suma del aporte de todas sus semanas
                        'start_date' => $activity->start_date ? Carbon::parse($activity->start_date)->format('Y-m-d') : null,
                        'end_date'   => $activity->end_date ? Carbon::parse($activity->end_date)->format('Y-m-d') : null,
                        'users' => ($activity->users ?? collect([]))->map(function ($user) {
                            return ['id' => $user->id, 'name' => $user->name ?? 'Sin nombre'];
                        })->toArray(),
                        'indicators' => ($activity->indicators ?? collect([]))->map(function ($indicator) {
                            return ['id' => $indicator->id, 'name' => $indicator->name];
                        })->toArray(),
                        'monthly_plannig' => ($activity->monthlyProgress ?? collect([]))->map(function ($progress) {
                            return ['month' => Carbon::parse($progress->month)->format('Y-m-d'), 'percentage' => $progress->percentage];
                        })->toArray(),
                        'execution_progress' => $executionProgress,
                    ];
                });

                // Calculamos el progreso total del producto sumando el progreso de sus actividades
                $totalProductProgress = $mappedActivities->sum('total_progress');

                return [
                    'id' => $product->id,
                    'name' => $product->name,
                    'budget'=>$product->budget,
                    'budget_type'=>$product->budget_type ? $product->budget_type->name : 'Sin definir',
                    'absolute_weight' => $productAbsoluteWeight, // Peso real en el 100% del proyecto
                    'total_progress' => $totalProductProgress, // Suma del aporte de todas sus actividades
                    'user' => $product->user ? ['id' => $product->user->id, 'name' => $product->user->name ?? 'Sin nombre'] : null,
                    'location' => $product->location ? ['id' => $product->location->id, 'name' => $product->location->name] : null,
                    'rubro' => $product->rubro ? ['id' => $product->rubro->id, 'name' => $product->rubro->name] : null,
                    'activities' => $mappedActivities->toArray(),
                ];
            });

            // Opcional: Calcular el avance total de todo el rubro
            $totalRubroProgress = $formattedProducts->sum('total_progress');

            return response()->json([
                'msg' => [
                    'summary' => 'Success',
                    'detail' => 'Productos obtenidos correctamente',
                    'code' => 200,
                ],
                'data' => [
                    'total_rubro_progress' => $totalRubroProgress,
                    'products' => $formattedProducts->values()->toArray(),
                ]
            ]);
        } catch (Exception $e) {
            Log::error('Error al obtener los productos en getProductsWithActivities: ' . $e->getMessage());
            return response()->json([
                'msg' => [
                    'summary' => 'Error',
                    'detail' => 'Error al obtener los productos: ' . $e->getMessage(),
                    'code' => 500,
                ],
            ], 500);
        }
    }

    public function getProductsWithActivitiesExtraPoa(Request $request)
    {
        try {
            $user = $request->user();

            $products = Product::where('location_id', $user->location_id)
                ->whereHas('rubro', function ($query) {
                    $query->where('name', '=', 'OFICIAL');
                })
                ->with([
                    'location',
                    'rubro',
                    'user', // Responsable del producto
                    'activities' => function ($query) {
                        // Carga la relación 'users' (responsables de la actividad), 'indicators',
                        // 'monthlyProgress' y 'weeklyActivities' (para el progreso de ejecución)
                        $query->with(['users', 'indicators', 'monthlyProgress', 'weeklyActivities']);
                    },
                ])->get();

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
                            'ponderacion' => $activity->ponderacion, // Asegurarse de que la ponderación de la actividad esté disponible
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
                            // Mapea las weeklyActivities para formar el array execution_progress
                            // Lógica de cálculo basada en la ponderación de la actividad
                            'execution_progress' => ($activity->weeklyActivities ?? collect([]))->map(function ($weekActivity) use ($activity) {
                                $activityPonderacion = (float) $activity->ponderacion;
                                $weekActivityPercentage = (float) $weekActivity->percentage;

                                // Cálculo: (Ponderación de la Actividad / 100) * Porcentaje de Avance Semanal
                                // Ejemplo: (25 / 100) * 50 = 12.5
                                $effectivePercentage = ($activityPonderacion / 100) * $weekActivityPercentage;

                                return [
                                    'month' => Carbon::parse($weekActivity->date)->format('Y-m-d'), // Usa la fecha de la WeekActivity
                                    'percentage' => (string) round($effectivePercentage, 2), // Usa el porcentaje calculado, redondeado a 2 decimales
                                    'observations' => $weekActivity->observations, // Incluye observaciones si es necesario
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
            Log::error('Error al obtener los productos en getProductsWithActivities: ' . $e->getMessage());
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
            })->with(['executionProgress' => function ($query) {
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

    //// Para obtener producto por estación
    public function getUniqueLocations()
    {
        try {
            $locations = Location::select('id', 'name')->get(); // Solo selecciona ID y nombre
            return response()->json([
                'msg' => [
                    'summary' => 'Success',
                    'detail' => 'Ubicaciones únicas obtenidas correctamente',
                    'code' => 200,
                ],
                'data' => $locations,
            ]);
        } catch (Exception $e) {
            Log::error('Error al obtener las ubicaciones únicas: ' . $e->getMessage());
            return response()->json([
                'msg' => [
                    'summary' => 'Error',
                    'detail' => 'Error al obtener las ubicaciones únicas: ' . $e->getMessage(),
                    'code' => 500,
                ],
            ], 500);
        }
    }

    public function getProductsByLocationId(Request $request, $locationId)
    {
        try {
            $products = Product::where('location_id', $locationId)
                ->whereHas('rubro', function ($query) {
                    $query->where('name', '!=', 'OFICIAL');
                })
                ->with([
                    'location',
                    'rubro',
                    'user',
                    'activities' => function ($query) {
                        $query->with(['users', 'indicators', 'monthlyProgress', 'weeklyActivities']);
                    },
                ])->get();

            // Mapear los datos y añadir los cálculos de ponderación (LÓGICA COPIADA)
            $formattedProducts = $products->map(function ($product) {

                $productAbsoluteWeight = (float) $product->ponderacion / 100;

                $mappedActivities = ($product->activities ?? collect([]))->map(function ($activity) use ($productAbsoluteWeight) {

                    $activityAbsoluteWeight = $productAbsoluteWeight * ((float) $activity->ponderacion / 100);
                    $totalActivityProgress = 0;

                    $executionProgress = ($activity->weeklyActivities ?? collect([]))->map(function ($weekActivity) use ($activityAbsoluteWeight, &$totalActivityProgress) {

                        $globalContribution = $activityAbsoluteWeight * ((float) $weekActivity->percentage / 100);
                        $totalActivityProgress += $globalContribution;

                        return [
                            'week_id' => $weekActivity->id,
                            'date' => Carbon::parse($weekActivity->date)->format('Y-m-d'),
                            'reported_percentage' => (float) $weekActivity->percentage,
                            'global_contribution' => $globalContribution,
                            'observations' => $weekActivity->observations,
                        ];
                    })->toArray();

                    return [
                        'id' => $activity->id,
                        'description' => $activity->description,
                        'relative_weight' => (float) $activity->ponderacion,
                        'absolute_weight' => $activityAbsoluteWeight,
                        'total_progress' => $totalActivityProgress,
                        'start_date' => $activity->start_date ? Carbon::parse($activity->start_date)->format('Y-m-d') : null,
                        'end_date'   => $activity->end_date ? Carbon::parse($activity->end_date)->format('Y-m-d') : null,
                        'users' => ($activity->users ?? collect([]))->map(function ($user) {
                            return ['id' => $user->id, 'name' => $user->name ?? 'Sin nombre'];
                        })->toArray(),
                        'indicators' => ($activity->indicators ?? collect([]))->map(function ($indicator) {
                            return ['id' => $indicator->id, 'name' => $indicator->name];
                        })->toArray(),
                        'monthly_plannig' => ($activity->monthlyProgress ?? collect([]))->map(function ($progress) {
                            return ['month' => Carbon::parse($progress->month)->format('Y-m-d'), 'percentage' => $progress->percentage];
                        })->toArray(),
                        'execution_progress' => $executionProgress,
                    ];
                });

                $totalProductProgress = $mappedActivities->sum('total_progress');

                return [
                    'id' => $product->id,
                    'name' => $product->name,
                    'absolute_weight' => $productAbsoluteWeight,
                    'total_progress' => $totalProductProgress,
                    'user' => $product->user ? ['id' => $product->user->id, 'name' => $product->user->name ?? 'Sin nombre'] : null,
                    'location' => $product->location ? ['id' => $product->location->id, 'name' => $product->location->name] : null,
                    'rubro' => $product->rubro ? ['id' => $product->rubro->id, 'name' => $product->rubro->name] : null,
                    'activities' => $mappedActivities->toArray(),
                ];
            });

            $totalRubroProgress = $formattedProducts->sum('total_progress');

            return response()->json([
                'msg' => [
                    'summary' => 'Success',
                    'detail' => 'Productos obtenidos por ubicación correctamente',
                    'code' => 200,
                ],
                'data' => [
                    'total_rubro_progress' => $totalRubroProgress,
                    'products' => $formattedProducts->values()->toArray(),
                ]
            ]);
        } catch (Exception $e) {
            Log::error('Error al obtener productos por ubicación: ' . $e->getMessage());
            return response()->json([
                'msg' => [
                    'summary' => 'Error',
                    'detail' => 'Error al obtener productos por ubicación: ' . $e->getMessage(),
                    'code' => 500,
                ],
            ], 500);
        }
    }

    public function getPlannableProductsForCurrentUser(Request $request)
    {
        $user = $request->user();
        $user->load('groups');
        $officialRubro = Rubro::whereRaw('LOWER(name) = ?', ['oficial'])->first();
        $officialRubroId = $officialRubro ? $officialRubro->id : null;
        $officialProducts = collect();
        if ($officialRubroId) {
            $officialProducts = Product::where('rubro_id', $officialRubroId)
                ->with(['activities.users', 'rubro', 'user', 'location'])
                ->get();
        }
        $specificProducts = collect();
        if ($user->groups->isNotEmpty()) {
            $groupPermissions = $user->groups->map(function ($group) {
                return ['rubro_id' => $group->rubro_id, 'location_id' => $group->location_id];
            })->unique(function ($item) {
                return $item['rubro_id'] . '-' . $item['location_id'];
            });
            if ($groupPermissions->isNotEmpty()) {
                $query = Product::query();
                $query->where(function ($q) use ($groupPermissions) {
                    foreach ($groupPermissions as $permission) {
                        $q->orWhere(function ($subQ) use ($permission) {
                            $subQ->where('rubro_id', $permission['rubro_id'])
                                ->where('location_id', $permission['location_id']);
                        });
                    }
                });
                if ($officialRubroId) {
                    $query->where('rubro_id', '!=', $officialRubroId);
                }
                $specificProducts = $query->with(['activities.users', 'rubro', 'user', 'location'])->get();
            }
        }
        else {
            $query = Product::query();
            $query->where(function ($q) use ($user) {
                $q->where('user_id', $user->id)
                    ->orWhereHas('activities.users', function($subQ) use ($user) {
                        $subQ->where('users.id', $user->id);
                    });
            });
            if ($officialRubroId) {
                $query->where('rubro_id', '!=', $officialRubroId);
            }
            $specificProducts = $query->with(['activities.users', 'rubro', 'user', 'location'])->get();
        }
        $allPlannableProducts = $officialProducts->merge($specificProducts)->unique('id');
        return response()->json(['data' => $allPlannableProducts->values()]);
    }

    public function storeMonthlyExecution(Request $request)
    {
        $request->validate([
            'reports' => ['required', 'array'],
            'reports.*.activity_id' => ['required', 'exists:activities,id'],
            'reports.*.month' => ['required', 'date_format:Y-m-d'],
            'reports.*.percentage' => ['required', 'numeric', 'min:0'],
            'reports.*.accrued_budget' => ['required'],
        ]);

        DB::beginTransaction();
        try {
            $user = $request->user();

            foreach ($request->reports as $report) {
                ActivityExecutionProgress::updateOrCreate(
                    [
                        'activity_id' => $report['activity_id'],
                        'month' => $report['month'],
                    ],
                    [
                        'percentage' => $report['percentage'],
                        // Podríamos añadir un campo user_id a la tabla si queremos saber quién reportó.
                    ]
                );
            }
            Log::debug('Detalle para la depuración: {acurred_budget}', [$report['accrued_budget']]);
            $activity = Activity::find($report['activity_id']);

                if ($activity) {
                    // Actualizamos el campo en la tabla activities
                    $activity->accrued_budget = $report['accrued_budget'];
                    $activity->save();
                }

            DB::commit();
            return response()->json([
                'msg' => ['summary' => 'Éxito', 'detail' => 'Reporte de avance mensual guardado correctamente.', 'code' => 201]
            ], 201);


        } catch (Exception $e) {
            return response()->json([
                'msg' => [
                    'summary' => 'Error',
                    'detail' => 'Error al enviar la ejecución mensual: ' . $e->getMessage(),
                    'code' => 500,
                ],
            ], 500);
        }
    }

    public function getActivitiesForMonthlyReport(Request $request)
    {
        $request->validate([
            'month' => 'nullable|date_format:Y-m',
        ]);

        try {
            $user = $request->user();

            $targetMonth = $request->has('month')
                ? Carbon::parse($request->input('month'))->startOfMonth()
                : Carbon::now()->subMonth()->startOfMonth();

            // --- INICIO DE LA NUEVA LÓGICA ---

            // 1. Obtener los IDs de todas las actividades que YA tienen un reporte este mes.
            // Consultamos la tabla de ejecución para ver qué actividades ya fueron reportadas por CUALQUIER usuario.
            $reportedActivityIds = \App\Modules\Planificacion\Models\ActivityExecutionProgress::where('month', $targetMonth)
                ->pluck('activity_id') // Obtenemos solo la columna activity_id
                ->unique(); // Nos aseguramos de que los IDs sean únicos

            // --- FIN DE LA NUEVA LÓGICA ---

            // 2. Busca todas las actividades del usuario, PERO excluye las que ya fueron reportadas.
            $activities = Activity::whereHas('users', function ($query) use ($user) {
                $query->where('users.id', $user->id);
            })
                ->whereNotIn('id', $reportedActivityIds) // <-- ¡LA CLAVE ESTÁ AQUÍ!
                ->with(['monthlyProgress' => function ($query) use ($targetMonth) {
                    $query->where('month', $targetMonth);
                }])
                ->get();

            $formattedData = $activities->map(function ($activity) use ($targetMonth) {
                $plannedProgress = $activity->monthlyProgress->first();
                return [
                    'id' => $activity->id,
                    'description' => $activity->description,
                    'month_to_report' => $targetMonth->format('Y-m-d'),
                    'planned_percentage' => $plannedProgress ? $plannedProgress->percentage : 0,
                    'budget'=>$activity->budget,
                ];
            });

            return response()->json(['data' => $formattedData]);

        } catch (Exception $e) {
            return response()->json([
                'msg' => [
                    'summary' => 'Error',
                    'detail' => 'Error al obtener la ejecución mensual: ' . $e->getMessage(),
                    'code' => 500,
                ],
            ], 500);
        }
    }
}
