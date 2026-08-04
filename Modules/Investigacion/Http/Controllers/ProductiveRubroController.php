<?php

namespace Modules\Investigacion\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Productive_Rubro;
use Illuminate\Http\Request;

class ProductiveRubroController extends Controller
{
    public function store(Request $request){
        $location= auth()->user()->location['id'];
        $request->validate([
            'name'=>'required|unique:productive_rubros,name',
        ]);

        $productiveRubro=Productive_Rubro::create([
            'name'=>$request->name,
        ]);

        return response()->json([
            'message'=> 'Rubro Productivo creado exitosamente',
            'data'=>$productiveRubro
        ],201);
    }

    public function index(){
        $productiveRubros = Productive_Rubro::all();

        if($productiveRubros->isEmpty()){
            return response()->json([
                'message'=>'No se encontraron Rubros Productivos',
            ], 404);
        }

        return $productiveRubros;
    }

    public function update(Request $request , $id){
        $request->validate([
            'name'=>'required|unique:productive_rubros,name,'.$id,
        ]);

        $productiveRubro = Productive_Rubro::find($id);

        if(!$productiveRubro){
            return response()->json(['message'=>'No encontrado'], 404);
        }

        $productiveRubro->update([
            'name' => $request->name
        ]);

        return response()->json([
            'message'=>'Rubro Productivo actualizado exitosamente',
            'data'=>$productiveRubro
        ], 200);
    }

    public function destroy($id){

        $productiveRubro=Productive_Rubro::find($id);
        if(!$productiveRubro){
            return response()->json([
                'message'=>'Rubro Productivo no encontrado'
            ],404);
        }
        $productiveRubro->delete();

        return response()->json([
            'message'=>'Rubro Productivo eliminado exitosamente'
        ],200);
    }
}
