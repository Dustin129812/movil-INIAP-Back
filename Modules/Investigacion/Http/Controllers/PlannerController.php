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
use Illuminate\Support\Facades\Auth;
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
            $product->status = 'pending';
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
                if ($product->status !== 'pending') {
                    $product->status = 'pending';
                }
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

                $monthlyData = $activityData['monthly_progress']
                    ?? $activityData['monthly_distribution']
                    ?? $activityData['monthly_planning']
                    ?? null;

                if (!empty($monthlyData)) {
                    // 2. Eliminamos registros previos para evitar duplicados al editar
                    $activity->monthlyProgress()->forceDelete();

                    foreach ($monthlyData as $plan) {
                        // Validar que el mes no sea nulo
                        if (empty($plan['month'])) continue;

                        $activity->monthlyProgress()->create([
                            // Usamos Carbon para asegurar el formato Y-m-d, ya que en tu JSON
                            // viene con formato ISO (2026-02-01T05:00:00.000000Z)
                            'month' => \Carbon\Carbon::parse($plan['month'])->startOfMonth()->format('Y-m-d'),
                            'percentage' => $plan['percentage']
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

            // Mantenemos la colección intacta como en tu código original
            $teamMemberIds = $revisor->groups->flatMap(function ($group) {
                return $group->members->pluck('id');
            })->unique();

            if ($teamMemberIds->isEmpty()) {
                return response()->json(['data' => []]);
            }

            $relevantStatuses = ['pending', 'approved', 'rejected', 'reassigned'];
            $period = $request->query('period', '15days');

            $dateFilter = null;
            if ($period === '7days') {
                $dateFilter = Carbon::now()->subDays(7);
            } elseif ($period === '15days') {
                $dateFilter = Carbon::now()->subDays(15);
            }

            // ====================================================================
            // 1. TAREAS PROPIAS (Las normales)
            // ====================================================================
            $ownQuery = WeekActivity::whereIn('status', $relevantStatuses)
                ->whereIn('user_id', $teamMemberIds)
                ->with([
                    'activity.product.rubro',
                    'activity.product.location',
                    'user',
                    'materials',
                    'activity.indicators'
                ]);

            if ($dateFilter) {
                $ownQuery->where('date', '>=', $dateFilter);
            }
            $ownActivities = $ownQuery->orderBy('date', 'desc')->get();

            // ====================================================================
            // 2. TAREAS DE APOYO
            // ====================================================================
            $supportQuery = WeekActivity::whereIn('status', $relevantStatuses)
                ->whereHas('logisticSupportUsers', function ($q) use ($teamMemberIds) {
                    $q->whereIn('users.id', $teamMemberIds)
                        ->whereIn('week_activity_logistic_support_user.status', ['accepted', 'pending']);
                })
                ->with([
                    'activity.product.rubro',
                    'activity.product.location',
                    'user',
                    'materials',
                    'activity.indicators',
                    'logisticSupportUsers'
                ]);

            if ($dateFilter) {
                $supportQuery->where('date', '>=', $dateFilter);
            }
            $supportActivities = $supportQuery->orderBy('date', 'desc')->get();

            // ====================================================================
            // 3. AGRUPACIÓN CON INYECCIÓN DE ATRIBUTOS (setAttribute)
            // ====================================================================
            $groupedByUser = [];
            $userNamesCache = [];

            foreach ($ownActivities as $act) {
                if (!$act->user || !$act->activity || !$act->activity->product) continue;

                // Inyección segura: Laravel lo serializará automáticamente en el JSON
                $act->setAttribute('is_owner', true);
                $act->setAttribute('display_user_id', $act->user_id);
                $act->setAttribute('display_user_name', $act->user->name);
                $act->setAttribute('ownerName', $act->user->name);

                $groupedByUser[$act->user_id][] = $act;
                $userNamesCache[$act->user_id] = $act->user->name;
            }

            foreach ($supportActivities as $act) {
                if (!$act->user || !$act->activity || !$act->activity->product) continue;

                foreach ($act->logisticSupportUsers as $supportUser) {
                    if ($teamMemberIds->contains($supportUser->id) && in_array($supportUser->pivot->status, ['accepted', 'pending'])) {
                        $clonedAct = clone $act;

                        // Inyección segura para los apoyos
                        $clonedAct->setAttribute('is_owner', false);
                        $clonedAct->setAttribute('display_user_id', $supportUser->id);
                        $clonedAct->setAttribute('display_user_name', $supportUser->name);
                        $clonedAct->setAttribute('ownerName', $act->user->name);

                        $groupedByUser[$supportUser->id][] = $clonedAct;
                        $userNamesCache[$supportUser->id] = $supportUser->name;
                    }
                }
            }

            // ====================================================================
            // 4. FORMATEO IDÉNTICO AL ORIGINAL
            // ====================================================================
            $formattedData = [];
            foreach ($groupedByUser as $userId => $activities) {
                $groupedByProduct = collect($activities)->groupBy('activity.product_id');

                $formattedData[] = [
                    'id' => $userId,
                    'name' => $userNamesCache[$userId] ?? 'Usuario',
                    'activities' => $groupedByProduct->map(function ($activitiesForProduct) {
                        $firstActivity = $activitiesForProduct->first();
                        return [
                            'product_id' => $firstActivity->activity->product->id,
                            'product_name' => $firstActivity->activity->product->name,
                            'activity_description' => $firstActivity->activity->name,
                            // Al dejar el ->values() intacto, dejamos que Laravel haga su magia de serialización sin romper nada
                            'week_activities' => $activitiesForProduct->values(),
                        ];
                    })->values()->toArray()
                ];
            }

            return response()->json(['data' => $formattedData]);

        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Error en getWeeklyPlanningByResponsible: ' . $e->getMessage(), [
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
        $user = Auth::user();
        $query = Product::with([
            'rubro',
            'activities.users',
            'activities.monthlyProgress',
            'activities.executionProgress',
            'activities.indicators',
            'location',
            'user',
            'crop',
            'budget_type'
        ]);

        if ($user->hasRole('administrador')) {
            if ($request->has('location_id') && $request->location_id != 'all') {
                $query->where('location_id', $request->location_id);
            }
        } else {

            if ($user->can('poa.view_all_locations')) {
                if ($request->has('location_id') && $request->location_id != 'all') {
                    $query->where('location_id', $request->location_id);
                }
            } else {
                $query->where('location_id', $user->location_id);
            }

            if (!empty($user->th_administrative_unit_id)) {
                $allowedRubroIds = DB::table('admin_poa_visibility')
                    ->where('th_administrative_unit_id', $user->th_administrative_unit_id)
                    ->pluck('rubro_id')
                    ->toArray();
                if (!empty($allowedRubroIds)) {
                    $query->whereIn('rubro_id', $allowedRubroIds);
                }
            }
        }

        if ($request->has('year')) {
            $query->where('year', $request->input('year'));
        }

        if ($request->has('rubro_id') && $request->rubro_id != 'all') {
            $query->where('rubro_id', $request->input('rubro_id'));
        }

        if ($request->has('search') && !empty($request->search)) {
            $search = $request->input('search');
            $query->where('name', 'LIKE', "%{$search}%");
        }

        $query->orderBy('id', 'desc');

        $perPage = $request->input('per_page', 500); // 500 registros por defecto
        return $query->paginate($perPage);
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
                        'monthly_planning' => ($activity->monthlyProgress ?? collect([]))->map(function ($progress) {
                            return ['month' => Carbon::parse($progress->month)->format('Y-m-d'), 'percentage' => $progress->percentage];
                        })->toArray(),
                        'execution_progress' => $executionProgress,
                    ];
                });

                $totalProductProgress = $mappedActivities->sum('total_progress');

                return [
                    'id' => $product->id,
                    'status' => $product->status,
                    'name' => $product->name,
                    'budget'=>$product->budget,
                    'crop'=>$product->crop ? ['id' => $product->crop->id, 'name' => $product->crop->name , 'productive_rubro_id' => $product->crop->productive_rubro_id] : null,
                    'budget_type'=>$product->budget_type ? $product->budget_type->name : 'Sin definir',
                    'budget_types_id' => $product->budget_types_id,
                    'funding_source_name' => $product->funding_source_name,
                    'ponderacion' => $product->ponderacion,
                    'absolute_weight' => $productAbsoluteWeight,
                    'total_progress' => $totalProductProgress,
                    'user' => $product->user ? [
                        'id' => $product->user->id,
                        'name' => $product->user->name ?? 'Sin nombre',
                        'last_name' => $product->user->last_name ?? ''
                    ] : null,                    'location' => $product->location ? ['id' => $product->location->id, 'name' => $product->location->name] : null,
                    'rubro' => $product->rubro ? ['id' => $product->rubro->id, 'name' => $product->rubro->name] : null,
                    'activities' => $mappedActivities->toArray(),
                    'create_at'=> $product->created_at ? Carbon::parse($product->created_at)->format('Y-m-d') : null, //cambio
                ];
            });

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
                    'user',
                    'activities' => function ($query) {
                        $query->with(['users', 'indicators', 'monthlyProgress', 'weeklyActivities']);
                    },
                ])->get();

            $formattedProducts = $products->map(function ($product) {
                return [
                    'id' => $product->id,
                    'status' => $product->status,
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
                            'execution_progress' => ($activity->weeklyActivities ?? collect([]))->map(function ($weekActivity) use ($activity) {
                                $activityPonderacion = (float) $activity->ponderacion;
                                $weekActivityPercentage = (float) $weekActivity->percentage;

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
            })->values()->toArray();

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

            $totalAssociatedProducts = Product::whereUserRelated($userId)->count();

            $activitiesAsResearcherCount = Activity::whereHas('users', function ($query) use ($userId) {
                $query->where('users.id', $userId);
            })->count();

            $completedActivitiesCount = 0;
            $userActivitiesWithProgress = Activity::whereHas('users', function ($query) use ($userId) {
                $query->where('users.id', $userId);
            })->with(['executionProgress' => function ($query) {
                $query->orderBy('month', 'desc');
            }])->get();

            foreach ($userActivitiesWithProgress as $activity) {
                $latestExecution = $activity->executionProgress->first();
                if ($latestExecution && $latestExecution->percentage === 100) {
                    $completedActivitiesCount++;
                }
            }

            $currentMonth = Carbon::now()->startOfMonth();
            $totalPlannedPercentage = 0;
            $totalExecutedPercentage = 0;
            $activitiesCountForMonthlyMetrics = 0;

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

                if ($planned) {
                    $totalPlannedPercentage += $planned->percentage;
                    $activitiesCountForMonthlyMetrics++;
                }
                if ($executed) {
                    $totalExecutedPercentage += $executed->percentage;
                    if (!$planned) {
                        $activitiesCountForMonthlyMetrics++;
                    }
                }
            }

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
            $user = $request->user();

            $query = Product::where('location_id', $locationId)
                ->with([
                    'location',
                    'rubro',
                    'users',
                    'user',
                    'budget_type',
                    'crop',
                    'activities' => function ($query) {
                        $query->with(['users', 'indicators', 'monthlyProgress', 'weeklyActivities', 'monthlyExecutionProgress']);
                    },
                ]);

            if (!$user->hasRole('administrador')) {
                $query->whereHas('rubro', function ($q) {
                    $q->where('name', '!=', 'OFICIAL');
                });
            }

            $products = $query->get();

            $formattedProducts = $products->map(function ($product) {

                $productAbsoluteWeight = (float) $product->ponderacion / 100;

                $mappedActivities = ($product->activities ?? collect([]))->map(function ($activity) use ($productAbsoluteWeight) {
                    $activityWeight = (float) $activity->ponderacion;
                    $activityAbsoluteWeight = $productAbsoluteWeight * ($activityWeight / 100);

                    $useMonthly = $activity->monthlyExecutionProgress->isNotEmpty();
                    $sourceCollection = $useMonthly ? $activity->monthlyExecutionProgress : ($activity->weeklyActivities ?? collect([]));
                    $totalRealProgress = $sourceCollection->sum('percentage');
                    $totalWeightedProgress = $activityAbsoluteWeight * ($totalRealProgress / 100);

                    $executionProgress = $sourceCollection->map(function ($item) use ($useMonthly) {
                        $dateValue = $useMonthly ? $item->month : $item->date;
                        return [
                            'id' => $item->id,
                            'week_id' => $useMonthly ? null : $item->id,
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
                        'users' => ($activity->users ?? collect([]))->map(function ($user) {
                            return ['id' => $user->id, 'name' => $user->name ?? 'Sin nombre', 'last_name' => $user->last_name ?? ''];
                        })->toArray(),
                        'user' => ($activity->users ?? collect([]))->map(function ($user) { // Compatibilidad
                            return ['id' => $user->id, 'name' => $user->name ?? 'Sin nombre', 'last_name' => $user->last_name ?? ''];
                        })->toArray(),
                        'indicators' => ($activity->indicators ?? collect([]))->map(function ($indicator) {
                            return ['id' => $indicator->id, 'name' => $indicator->name];
                        })->toArray(),
                        'monthly_planning' => ($activity->monthlyProgress ?? collect([]))->map(function ($progress) {
                            return ['month' => Carbon::parse($progress->month)->format('Y-m-d'), 'percentage' => $progress->percentage];
                        })->toArray(),
                        'execution_progress' => $executionProgress,
                    ];
                });

                $totalProductProgress = $mappedActivities->sum('total_progress');
                $responsibleUser = $product->users->first() ?? $product->user;

                return [
                    'id' => $product->id,
                    'status' => $product->status,
                    'name' => $product->name,
                    'ponderacion' => $product->ponderacion,
                    'crop' => $product->crop ? ['id' => $product->crop->id, 'name' => $product->crop->name , 'productive_rubro_id' => $product->crop->productive_rubro_id] : null,
                    'crop_id' => $product->crop_id,
                    'create_at'=> $product->created_at ? Carbon::parse($product->created_at)->format('Y-m-d') : null,
                    'budget_type' => $product->budget_type ? $product->budget_type->name : 'Sin definir',
                    'budget_types_id' => $product->budget_types_id,
                    'funding_source_name' => $product->funding_source_name,
                    'budget' => $product->budget,
                    'absolute_weight' => $productAbsoluteWeight,
                    'total_progress' => $totalProductProgress,

                    'user' => $responsibleUser ? [
                        'id' => $responsibleUser->id,
                        'name' => $responsibleUser->name ?? 'Sin nombre',
                        'last_name' => $responsibleUser->last_name ?? ''
                    ] : null,
                    'user_id' => $responsibleUser ? $responsibleUser->id : null, // ID directo para facilitar binding

                    'location' => $product->location ? ['id' => $product->location->id, 'name' => $product->location->name] : null,
                    'rubro' => $product->rubro ? ['id' => $product->rubro->id, 'name' => $product->rubro->name] : null,
                    'rubro_id' => $product->rubro_id, // ID directo
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

        $officialRubro = Rubro::where('name', 'LIKE', '%OFICIAL%')->first();
        $officialRubroId = $officialRubro ? $officialRubro->id : null;

        $officialProducts = collect();
        if ($officialRubroId) {
            $officialProducts = Product::where('rubro_id', $officialRubroId)
                ->where('location_id', $user->location_id)
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

        } else {
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

    /**
     * Cambiar el estado de aprobación de un Producto (POA).
     */
    public function reviewProduct(Request $request, $productId)
    {
        if (!auth()->user()->can('poa.review')) {
            return response()->json([
                'msg' => [
                    'summary' => 'No autorizado',
                    'detail' => 'No tienes permisos para revisar o aprobar productos.',
                    'code' => 403,
                ],
            ], 403);
        }

        $request->validate([
            'status' => 'required|in:approved,rejected,pending',
            'observation' => 'nullable|string'
        ]);

        DB::beginTransaction();
        try {
            $product = Product::findOrFail($productId);

            $oldStatus = $product->status;
            $newStatus = $request->input('status');
            $observation = $request->input('observation');

            // Actualizamos el producto
            $product->status = $newStatus;

            // Si se rechaza, es obligatorio guardar la observación.
            // Si se aprueba, podemos limpiar la observación anterior o dejarla como historial.
            if ($newStatus === 'rejected') {
                $product->admin_observation = $observation;
            } elseif ($newStatus === 'approved') {
                // Opcional: Limpiar observación al aprobar
                $product->admin_observation = null;
            }

            $product->save();

            // Aquí podrías notificar al dueño del producto
            // $product->user->notify(new ProductStatusChanged($product));

            DB::commit();

            return response()->json([
                'msg' => [
                    'summary' => 'Estado Actualizado',
                    'detail' => "El producto ha sido marcado como " . ($newStatus === 'approved' ? 'Aprobado' : 'Rechazado'),
                    'code' => 200,
                ],
                'data' => [
                    'id' => $product->id,
                    'status' => $product->status,
                    'observation' => $product->admin_observation
                ]
            ], 200);

        } catch (Exception $e) {
            DB::rollBack();
            Log::error("Error reviewing product: " . $e->getMessage());
            return response()->json([
                'msg' => [
                    'summary' => 'Error',
                    'detail' => 'No se pudo actualizar el estado del producto.',
                    'code' => 500,
                ],
            ], 500);
        }
    }
}
