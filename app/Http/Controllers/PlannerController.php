<?php

namespace App\Http\Controllers;


use App\Http\Resources\WeeklyPlannerResource;
use App\Models\Activity;
use App\Models\Location;
use App\Models\Performance_Indicator;
use App\Models\Product;
use App\Models\Rubro;
use App\Models\User;
use App\Models\WeekActivity;
use App\Models\WeekPlanner;
use Carbon\Carbon;
use Illuminate\Http\Request;
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
            $product->ponderacion = $request->input('ponderacion');

            $responsibleUser = User::find($request->input('user'));
            $product->user()->associate($responsibleUser);

            if (!$responsibleUser->hasRole('product-manager')) {
                $responsibleUser->assignRole('product-manager');
            }

            $product->rubro()->associate(Rubro::find($request->input('rubro')));
            $product->location()->associate($userLocation);

            $product->save();

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

            $nextMonday = Carbon::now()->startOfWeek(Carbon::MONDAY)->addWeek();

            foreach ($weekData as $day => $data) {
                // Crear WeekActivity
                $weekActivity = new WeekActivity();
                $weekActivity->description = $data['description'] ?? '';
                $weekActivity->date = $nextMonday;
                $weekActivity->material = json_encode($data['materials']);

                // Asocia actividad por día
                $weekActivity->activity()->associate(Activity::find($data['activity_id']));
                $weekActivity->save();

                // Crear WeekPlanner
                $planner = new WeekPlanner();
                $planner->product()->associate(Product::find($productId));
                $planner->weekActivity()->associate($weekActivity);
                $planner->save();

                // Avanzar al siguiente día
                $nextMonday->addDay();
            }

            DB::commit();

            return response()->json(['message' => 'Planificación guardada correctamente.']);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function getWeeklyPlanningByResponsible()
    {
        $responsables = User::whereHas('activities.weekActivities.weekPlanner')
            ->with([
                'activities' => function ($q) {
                    $q->whereHas('weekActivities.weekPlanner');
                    $q->with([
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
}
