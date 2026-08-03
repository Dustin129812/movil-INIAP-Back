<?php

namespace Modules\Investigacion\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Crops;
use Illuminate\Http\Request;

class CropsController extends Controller
{

    public function getCrops()
    {
        $crops = Crops::all();

        return $crops;
    }
    public function getCropsbyProductiveRubro($id)
    {

        $crops = Crops::with('productive_rubro')->where('productive_rubro_id', $id)->get();

        return $crops;
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string',
            'productive_rubro_id' => 'required|integer'
        ]);

        $crop = Crops::create([
            'name' => $request->name,
            'productive_rubro_id' => $request->productive_rubro_id
        ]);

        return response()->json([
            'message' => 'Cultivo creado exitosamente',
            'data' => $crop
        ], 201);
    }

    public function editCrops(Request $request)
    {
        $request->validate([
            'id' => 'required|integer',
            'name' => 'required|string',
            'productive_rubro_id' => 'required|integer'
        ]);

        $crop = Crops::find($request->id);

        if (!$crop) {
            return response()->json(['message' => 'Cultivo no encontrado'], 404);
        }

        $crop->update([
            'name' => $request->name,
            'productive_rubro_id' => $request->productive_rubro_id
        ]);

        return response()->json([
            'message' => 'Cultivo actualizado exitosamente',
            'data' => $crop
        ], 200);
    }

    public function deleteCrops(Request $request)
    {
        $request->validate([
            'id' => 'required|integer'
        ]);

        $crop = Crops::find($request->id);

        if (!$crop) {
            return response()->json(['message' => 'Cultivo no encontrado'], 404);
        }

        $crop->delete();

        return response()->json([
            'message' => 'Cultivo eliminado exitosamente'
        ], 200);
    }
}
