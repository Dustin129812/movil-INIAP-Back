<?php

namespace Modules\Investigacion\Http\Controllers;
use App\Http\Controllers\Controller;
use App\Models\User;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Modules\Investigacion\Entities\Activity;
use Modules\Investigacion\Entities\Location;
use Modules\Investigacion\Entities\Product;
use Modules\Investigacion\Entities\Rubro;
use Modules\Investigacion\Entities\WeekActivity;
use Modules\Investigacion\Notifications\CreateProduct;
use Modules\Investigacion\Notifications\PlannerAccept;
use Modules\Investigacion\Notifications\ProductUpdated;

class PlannerController extends Controller
{
    public function addProductAndActivity(Request $request)
    {
        DB::beginTransaction();

        try {
            $userLocation = auth()->user()->location;

            $product = new Product();
            $product->name = $request->input('name');
            $product->budget = (float) $request->input('budget');
            $product->budget_types_id = $request->input('budget_types_id');
            $product->ponderacion = $request->input('ponderacion');
            $product->funding_source_name = $request->input('funding_source_name');

            $product->rubro_id = $request->input('rubro');
            $product->crop_id = $request->input('crops');
            $product->location_id = $userLocation->id;
            $product->save();

            $userIds = $request->input('users', []);
            if (!empty($userIds)) {
                $product->users()->sync($userIds);

                foreach ($userIds as $uid) {
                    $user = User::find($uid);
                    if ($user) {
                        $user->assignRole('product-manager');

                        // Notificar a los responsables (excepto si es el mismo que crea)
                        if ($user->id !== auth()->id()) {
                            $user->notify(new CreateProduct($product, auth()->user()));
                        }
                    }
                }
            }

            foreach ($request->input('activities', []) as $activityData) {
                $activity = new Activity();
                $activity->description = $activityData['description'];
                $activity->budget = (float) $activityData['budget']; // Decimales
                $activity->accrued_budget = (float) $activityData['accrued_budget'];
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
                        $responsible->notify(new \Modules\Investigacion\Notifications\CreateActivity($activity, auth()->user()));
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
                    'detail' => 'Error al guardar: ' . $e->getMessage(),
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

            $product->name = $request->input('name');
            $product->budget = (float) $request->input('budget');
            $product->budget_types_id = $request->input('budget_types_id');
            $product->ponderacion = $request->input('ponderacion');

            $incomingRubro = $request->input('rubro_id') ?? $request->input('rubro');

            if (!empty($incomingRubro)) {
                $product->rubro_id = $incomingRubro;
            }

            if ($request->has('crops')) {
                $product->crop_id = $request->input('crops');
            }

            $product->funding_source_name = $request->input('funding_source_name');
            $product->save();

            $userIds = $request->input('users', []);
            if (empty($userIds) && $request->has('user_id') && $request->input('user_id')) {
                $userIds = [$request->input('user_id')];
            }

            if (!empty($userIds)) {
                $product->users()->sync($userIds);
                foreach ($userIds as $uid) {
                    $user = User::find($uid);
                    if ($user) {
                        $user->assignRole('product-manager');
                        if ($user->id !== auth()->id()) {
                            $user->notify(new ProductUpdated($product, auth()->user()));
                        }
                    }
                }
            }

            $receivedActivityIds = [];

            foreach ($request->input('activities', []) as $activityData) {
                $activity = Activity::findOrNew($activityData['id'] ?? null);

                $activity->description = $activityData['description'];
                $activity->budget = (float) $activityData['budget'];
                $activity->accrued_budget = (float) ($activityData['accrued_budget'] ?? 0);
                $activity->ponderacion = $activityData['ponderacion'];
                $activity->start_date = $activityData['start_date'];
                $activity->end_date = $activityData['end_date'];
                $activity->product_id = $product->id;
                $activity->save();

                $actUserIds = $activityData['users'] ?? $activityData['user'] ?? [];
                $indicatorIds = $activityData['indicators'] ?? [];

                $activity->users()->sync($actUserIds);
                $activity->indicators()->sync($indicatorIds);

                foreach ($actUserIds as $userId) {
                    $responsible = User::find($userId);
                    if ($responsible) {
                        $responsible->assignRole('researcher');
                    }
                }

                $activity->monthlyProgress()->delete();

                $monthlyData = $activityData['monthly_planning'] ?? $activityData['monthly_distribution'] ?? [];

                if (!empty($monthlyData)) {
                    foreach ($monthlyData as $monthData) {
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

            return response()->json([
                'msg' => [
                    'summary' => 'Actualización Exitosa',
                    'detail' => 'El producto y sus actividades han sido actualizados correctamente',
                    'code' => 200,
                ],
            ], 200);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Error update POA: " . $e->getMessage());
            return response()->json([
                'msg' => [
                    'summary' => 'Error',
                    'detail' => 'Error al actualizar: ' . $e->getMessage(),
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
            Carbon::setLocale('es');
            $user = $request->user();

            $products = Product::where('location_id', $user->location_id)
                ->whereHas('rubro', function ($query) {
                    $query->where('name', '!=', 'OFICIAL');
                })
                ->with([
                    'location',
                    'rubro',
                    'users',
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

                $totalProductProgress = $mappedActivities->sum('total_progress');

                return [
                    'id' => $product->id,
                    'name' => $product->name,
                    'budget'=>$product->budget,
                    'crop'=>$product->crop ? ['id' => $product->crop->id, 'name' => $product->crop->name , 'productive_rubro_id' => $product->crop->productive_rubro_id] : null,
                    'budget_type'=>$product->budget_type ? $product->budget_type->name : 'Sin definir',
                    'budget_types_id' => $product->budget_types_id,
                    'ponderacion' => $product->ponderacion,
                    'absolute_weight' => $productAbsoluteWeight, // Peso real en el 100% del proyecto
                    'total_progress' => $totalProductProgress, // Suma del aporte de todas sus actividades
                    'user' => $product->user ? [
                        'id' => $product->user->id,
                        'name' => $product->user->name ?? 'Sin nombre',
                        'last_name' => $product->user->last_name ?? '' // <--- AGREGAR ESTO
                    ] : null,                    'location' => $product->location ? ['id' => $product->location->id, 'name' => $product->location->name] : null,
                    'rubro' => $product->rubro ? ['id' => $product->rubro->id, 'name' => $product->rubro->name] : null,
                    'activities' => $mappedActivities->toArray(),
                    'create_at'=> $product->created_at ? Carbon::parse($product->created_at)->format('Y-m-d') : null,
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
    public function getAllProductsWithActivities(Request $request)
    {
        try {
            $user = $request->user();

            $products = Product::whereHas('rubro', function ($query) {
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
                    'crop'=>$product->crop ? ['id' => $product->crop->id, 'name' => $product->crop->name , 'productive_rubro_id' => $product->crop->productive_rubro_id] : null,
                    'budget_type'=>$product->budget_type ? $product->budget_type->name : 'Sin definir',
                    'budget_types_id' => $product->budget_types_id,
                    'ponderacion' => $product->ponderacion,
                    'absolute_weight' => $productAbsoluteWeight,
                    'total_progress' => $totalProductProgress,
                    'user' => $product->user ? [
                        'id' => $product->user->id,
                        'name' => $product->user->name ?? 'Sin nombre',
                        'last_name' => $product->user->last_name ?? '' // <--- AGREGAR ESTO
                    ] : null,                    'location' => $product->location ? ['id' => $product->location->id, 'name' => $product->location->name] : null,
                    'rubro' => $product->rubro ? ['id' => $product->rubro->id, 'name' => $product->rubro->name] : null,
                    'activities' => $mappedActivities->toArray(),
                    'create_at'=> $product->created_at ? Carbon::parse($product->created_at)->format('Y-m-d') : null, //cambio
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
                    'budget_type',
                    'crop',
                    'activities' => function ($query) {
                        // 1. IMPORTANTE: Agregamos 'monthlyExecutionProgress' a la consulta
                        $query->with(['users', 'indicators', 'monthlyProgress', 'weeklyActivities', 'monthlyExecutionProgress']);
                    },
                ])->get();

            $formattedProducts = $products->map(function ($product) {

                $productAbsoluteWeight = (float) $product->ponderacion / 100;

                $mappedActivities = ($product->activities ?? collect([]))->map(function ($activity) use ($productAbsoluteWeight) {

                    $activityWeight = (float) $activity->ponderacion;
                    $activityAbsoluteWeight = $productAbsoluteWeight * ($activityWeight / 100);

                    $useMonthly = $activity->monthlyExecutionProgress->isNotEmpty();

                    $sourceCollection = $useMonthly
                        ? $activity->monthlyExecutionProgress
                        : ($activity->weeklyActivities ?? collect([]));

                    $totalRealProgress = $sourceCollection->sum('percentage'); // Suma directa (0-100)
                    $totalWeightedProgress = $activityAbsoluteWeight * ($totalRealProgress / 100); // Contribución al proyecto

                    $executionProgress = $sourceCollection->map(function ($item) use ($useMonthly) {
                        $dateValue = $useMonthly ? $item->month : $item->date;

                        return [
                            'id' => $item->id,
                            'week_id' => $useMonthly ? null : $item->id, // Mantener compatibilidad si algo viejo lo usa
                            'month' => Carbon::parse($dateValue)->format('Y-m-d'),
                            'date' => Carbon::parse($dateValue)->format('Y-m-d'),
                            'reported_percentage' => (float) $item->percentage,
                            'observations' => $item->observations ?? '',
                        ];
                    })->toArray();

                    return [
                        'id' => $activity->id,
                        'description' => $activity->description,
                        'accrued_budget'=> $activity->accrued_budget,
                        'budget' => $activity->budget,
                        'ponderacion' => $activity->ponderacion,
                        'relative_weight' => $activityWeight,
                        'absolute_weight' => $activityAbsoluteWeight,
                        'total_progress' => $totalWeightedProgress,
                        'progreso_real' => $totalRealProgress,
                        'start_date' => $activity->start_date ? Carbon::parse($activity->start_date)->format('Y-m-d') : null,
                        'end_date'   => $activity->end_date ? Carbon::parse($activity->end_date)->format('Y-m-d') : null,
                        'user' => $product->user ? [
                            'id' => $product->user->id,
                            'name' => $product->user->name ?? 'Sin nombre',
                            'last_name' => $product->user->last_name ?? '' // <--- AGREGAR ESTO
                        ] : null,
                        'indicators' => ($activity->indicators ?? collect([]))->map(function ($indicator) {
                            return ['id' => $indicator->id, 'name' => $indicator->name];
                        })->toArray(),
                        'monthly_plannig' => ($activity->monthlyProgress ?? collect([]))->map(function ($progress) {
                            return ['month' => Carbon::parse($progress->month)->format('Y-m-d'), 'percentage' => $progress->percentage];
                        })->toArray(),

                        // Aquí va el array lleno
                        'execution_progress' => $executionProgress,
                    ];
                });

                $totalProductProgress = $mappedActivities->sum('total_progress');

                return [
                    'id' => $product->id,
                    'name' => $product->name,
                    'ponderacion' => $product->ponderacion,
                    'crop' => $product->crop ? ['id' => $product->crop->id, 'name' => $product->crop->name , 'productive_rubro_id' => $product->crop->productive_rubro_id] : null,
                    'create_at'=> $product->created_at ? Carbon::parse($product->created_at)->format('Y-m-d') : null,
                    'budget_type' => $product->budget_type ? $product->budget_type->name : 'Sin definir',
                    'budget_types_id' => $product->budget_types_id, // <--- CORRECCIÓN IMPORTANTE
                    'budget' => $product->budget,
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
}
