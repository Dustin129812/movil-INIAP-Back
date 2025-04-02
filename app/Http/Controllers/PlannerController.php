<?php

namespace App\Http\Controllers;


use App\Models\Activity;
use App\Models\Performance_Indicator;
use App\Models\Product;
use App\Models\Rubro;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PlannerController extends Controller
{
    public function addProductAndActivity(Request $request)
    {
        DB::beginTransaction();

        Log::info('Datos recibidos para agregar producto y actividades:', $request->all());

        try {
            $product = new Product();
            $product->name = $request->input('name');
            $product->budget = $request->input('budget');

            $product->user()->associate(User::find($request->input('user')));
            $product->rubro()->associate(Rubro::find($request->input('rubro')));

            $product->save();

            foreach ($request->input('activities', []) as $activityData) {
                Log::info('Actividad a guardar:', $activityData);
                $activity = new Activity();
                $activity->description = $activityData['description'];
                $activity->budget = $request->input('budget');

                $activity->user()->associate(User::find($request->input('user')));
                $activity->product()->associate($product);
                $activity->indicator()->associate(Performance_Indicator::find($activityData['indicator']));

                $indicator = Performance_Indicator::find($request->input('indicator'));
                if ($indicator) {
                    Log::info('Indicador encontrado:', ['indicator_id' => $indicator->id]);
                } else {
                    Log::error('Indicador no encontrado:', ['indicator_id' => $request->input('indicator')]);
                }


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
}
