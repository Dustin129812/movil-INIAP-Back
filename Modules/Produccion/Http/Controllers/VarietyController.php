<?php

namespace Modules\Produccion\Http\Controllers;

use Illuminate\Routing\Controller;
use Illuminate\Http\Request;
use Modules\Produccion\Entities\Variety;

class VarietyController extends Controller
{
    public function index()
    {
        // Retornamos con relaciones para que el front pueda mostrar nombres (ej: "Tomate - Cherry")
        return response()->json(Variety::with(['crop', 'category', 'type'])->get());
    }

    public function store(Request $request)
    {
        $request->validate([
            'name'                => 'required|string|max:255',
            'productive_rubro_id' => 'required|exists:productive_rubros,id',
            'crop_id'             => 'required|exists:crops,id',
            'category_id'         => 'required|exists:categories,id',
            'variety_type_id'     => 'required|exists:variety_types,id',
        ]);

        $variety = Variety::create($request->all());

        return response()->json([
            'message' => 'Variedad creada correctamente',
            'data' => $variety
        ], 201);
    }
}
