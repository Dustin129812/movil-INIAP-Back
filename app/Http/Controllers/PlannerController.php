<?php

namespace App\Http\Controllers;

use App\Http\Resources\WeeklyPlannerResource;
use App\Models\Activity;
use App\Models\Material;
use App\Models\Performance_Indicator;
use App\Models\Product;
use App\Models\Rubro;
use App\Models\User;
use App\Models\WeekActivity;
use App\Models\WeekPlanner;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class PlannerController extends Controller
{
    public function addProductAndActivity(Request $request)
    {
        DB::beginTransaction();

        try {
            $userLocation = auth()->user()->location;

            // Crear el producto
            $product = new Product();
            $product->name = $request->input('name');
            $product->budget = $request->input('budget');
            $product->ponderacion = $request->input('ponderacion');
            $product->user_id = $request->input('user');
            $product->rubro_id = $request->input('rubro');
            $product->location_id = $userLocation->id;
            $product->save();

            // Asignar rol al usuario
            $user = User::find($request->input('user'));
            if ($user) {
                $user->assignRole('product-manager');
            }

            // Procesar actividades
            foreach ($request->input('activities', []) as $activityData) {
                // Crear la actividad
                $activity = new Activity();
                $activity->description = $activityData['description'];
                $activity->budget = $activityData['budget'];
                $activity->ponderacion = $activityData['ponderacion'];
                $activity->start_date = $activityData['start_date'];
                $activity->end_date = $activityData['end_date'];
                $activity->user_id = $activityData['user'];
                $activity->product_id = $product->id;
                $activity->indicator_id = $activityData['indicator'];
                $activity->save();

                // Guardar la distribución mensual
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
                    'summary' => 'Success',
                    'detail' => 'El producto y sus actividades han sido guardados correctamente',
                    'code' => 201,
                ],
            ], 201);
        } catch (\Exception $e) {
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

    public function getMaterial()
    {
        $materials = Material::all();

        return response()->json($materials);
    }

    public function approveActivity(Request $request, $activityId)
    {
        try {
            if (!auth()->user()->hasRole('product-manager')) {
                return response()->json(['error' => 'No autorizado. Solo un product-manager puede aprobar actividades.'], 403);
            }

            // Obtener la actividad
            $weekActivity = WeekActivity::findOrFail($activityId);

            // Actualizar el estado según el valor enviado
            $status = $request->input('status');
            if (!in_array($status, ['approved', 'rejected'])) {
                return response()->json(['error' => 'Estado inválido. Use "approved" o "rejected".'], 400);
            }

            $weekActivity->status = $status;
            $weekActivity->save();

            return response()->json(['message' => 'Actividad actualizada correctamente.']);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage(), 'stack' => $e->getTraceAsString()], 500);
        }
    }

    public function getWeeklyPlanningByResponsible()
    {
        $responsables = User::whereHas('activities.weekActivities.weekPlanner')
            ->with([
                'activities' => function ($q) {
                    $q->whereHas('weekActivities.weekPlanner')
                        ->with([
                            'product',
                            'weekActivities' => function ($q2) {
                                $q2->whereHas('weekPlanner')
                                    ->with([
                                        'weekPlanner.product',
                                        'materials' // <- Asegúrate de incluir esta relación aquí
                                    ]);
                            }
                        ]);
                }
            ])
            ->get();

        return WeeklyPlannerResource::collection($responsables);
    }

    public function getProductsWithActivities(Request $request)
    {
        try {
            // Obtener el usuario autenticado (opcional)
            $user = $request->user();

            // Cargar productos con todas las relaciones necesarias
            $products = Product::with([
                'location',
                'rubro',
                'activities.user',
                'activities.indicator',
                'activities.monthlyProgress',
                'activities.executionProgress',
            ])->get();

            // Mapear los datos para asegurar el formato esperado
            $formattedProducts = $products->map(function ($product) {
                return [
                    'id' => $product->id,
                    'name' => $product->name,
                    'budget' => $product->budget,
                    'ponderacion' => $product->ponderacion,
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
                            'start_date' => $activity->start_date,
                            'end_date' => $activity->end_date,
                            'user' => $activity->user ? [
                                'id' => $activity->user->id,
                                'name' => $activity->user->name,
                            ] : null,
                            'indicator' => $activity->indicator ? [
                                'id' => $activity->indicator->id,
                                'name' => $activity->indicator->name,
                            ] : null,
                            'monthly_progress' => ($activity->monthlyProgress ?? collect([]))->map(function ($progress) {
                                return [
                                    'month' => $progress->month->format('Y-m-d'),
                                    'percentage' => $progress->percentage,
                                ];
                            })->toArray(),
                            'execution_progress' => ($activity->executionProgress ?? collect([]))->map(function ($progress) {
                                return [
                                    'month' => $progress->month->format('Y-m-d'),
                                    'percentage' => $progress->percentage,
                                ];
                            })->toArray(),
                        ];
                    })->toArray(),
                ];
            })->toArray();

            return response()->json([
                'msg' => [
                    'summary' => 'Success',
                    'detail' => 'Productos obtenidos correctamente',
                    'code' => 200,
                ],
                'data' => $formattedProducts,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'msg' => [
                    'summary' => 'Error',
                    'detail' => 'Error al obtener los productos: ' . $e->getMessage(),
                    'code' => 500,
                ],
            ], 500);
        }
    }
}
