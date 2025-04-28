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

            $product = new Product();
            $product->name = $request->input('name');
            $product->budget = $request->input('budget');
            $product->ponderacion = $request->input('ponderacion');

            $user = User::find($request->input('user'));

            $product->user()->associate(User::find($request->input('user')));
            $product->rubro()->associate(Rubro::find($request->input('rubro')));
            $product->location()->associate($userLocation);

            $product->save();

            if ($user) {
                $user->assignRole('product-manager');
            }

            foreach ($request->input('activities', []) as $activityData) {
                $activity = new Activity();
                $activity->description = $activityData['description'];
                $activity->budget = $activityData['budget']; // Corrige esto también
                $activity->ponderacion = $activityData['ponderacion'];
                $activity->fecha_inicio = $activityData['start_date'];
                $activity->fecha_fin = $activityData['end_date'];

                $activity->user()->associate(User::find($activityData['user']));
                $activity->product()->associate($product);
                $activity->indicator()->associate(Performance_Indicator::find($activityData['indicator']));
                $activity->save();

            }

            DB::commit();

            return response()->json([
                'msg' => [
                    'summary' => 'Success',
                    'detail' => 'El producto y sus actividades han sido guardados correctamente',
                    'code' => 201
                ]
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'msg' => [
                    'summary' => 'Error',
                    'detail' => 'Error al guardar el producto y actividades: ' . $e->getMessage(),
                    'code' => 500
                ]
            ], 500);
        }
    }


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

                $dayOffset = $daysOfWeek[$day] ?? 0;
                $activityDate = $nextMonday->copy()->addDays($dayOffset);

                $weekActivity = new WeekActivity();
                $weekActivity->description = $data['description'] ?? '';
                $weekActivity->date = $activityDate;
                $weekActivity->status = 'pending';
                $weekActivity->activity()->associate($activity);
                $weekActivity->save();

                // Asociar materiales (opcional)
                if (!empty($data['materials'])) {
                    foreach ($data['materials'] as $materialData) {
                        $materialId = $materialData['material_id'];
                        $quantity = $materialData['quantity'] ?? 1;

                        $material = Material::find($materialId);
                        if (!$material) {
                            throw new \Exception("Material con ID {$materialId} no encontrado.");
                        }

                        $weekActivity->materials()->attach($materialId, ['quantity' => $quantity]);
                    }
                }

                // Crear planner
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
                            'product', // Cargar la relación product para product_name
                            'weekActivities' => function ($q2) {
                                $q2->whereHas('weekPlanner')
                                    ->with('weekPlanner.product');
                            }
                        ]);
                }
            ])
            ->get();

        return WeeklyPlannerResource::collection($responsables);
    }

    public function getProductsWithActivities()
    {
        // Obtener todos los productos y sus actividades sin filtrar por el usuario
        $products = Product::with([
            'location',      // Relación con la ubicación
            'rubro',         // Relación con el rubro
            'activity.user', // Relación con la actividad y el usuario asociado
            'activity.indicator'
        ])
            ->get();  // Obtiene todos los productos sin restricción de usuario

        return response()->json($products);
    }
}
