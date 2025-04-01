<?php

namespace App\Http\Controllers;

use App\Models\Activity;
use App\Models\Investigation_Area;
use App\Models\Investigation_Line;
use App\Models\Location;
use App\Models\Multidisciplinary_Group;
use App\Models\Objetive;
use App\Models\Pei;
use App\Models\Performance_Indicator;
use App\Models\Rubro;
use App\Models\User;
use Illuminate\Http\Request;

class PlannerController extends Controller
{
    public function getObjetive()
    {
        return response()->json([
            'msg' => [
                'summary' => 'success',
                'detail' => 'Objetivo devuelto correctamente',
                'code' => 200
            ],
            'objetivo' => Objetive::all(),
        ], 200);
    }

    public function getActivity()
    {
        return response()->json([
            'msg' => [
                'summary' => 'success',
                'detail' => 'Actividad devuelta correctamente',
                'code' => 200
            ],
            'actividad' => Activity::all(),
        ], 200);
    }

    public function addObjetive(Request $request){
        $objetive = new Objetive();
        $objetive->name = $request->input('name');
        $objetive->save();

        return response()->json([
            'msg' => ['objetivo agregado correctamente']
        ]);
    }

    public function addActivity(Request $request){
        $activity = new Activity();
        $activity->name = $request->input('name');
        $activity->objetive()->associate(Objetive::find($request->input('objetive')));
        $activity->save();

        return response()->json([
            'msg' => ['actividad agregado correctamente']
        ]);
    }

    public function addPei(Request $request){
        $pei = new Pei();
        $pei->expected_results = $request->input('expected_results');
        $pei->locations()->associate(Location::find($request->input('location')));
        $pei->multidisciplinary_group()->associate(Multidisciplinary_Group::find($request->input('groups')));
        $pei->rubro()->associate(Rubro::find($request->input('rubro')));
        $pei->user()->associate(User::find($request->input('user')));
        $pei->investigation_area()->associate(Investigation_Area::find($request->input('investigation_area')));
        $pei->investigation_line()->associate(Investigation_Line::find($request->input('investigation_line')));
        $pei->objetive()->associate(Objetive::find($request->input('objetive')));
        $pei->performance_indicator()->associate(Performance_Indicator::find($request->input('performance_indicator')));

        $pei->save();
        return response()->json([
            'msg' => ['Planificacion Institucional agregada correctamente']
        ]);
    }
}
