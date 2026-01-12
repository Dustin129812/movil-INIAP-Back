<?php

namespace Modules\Produccion\Http\Controllers;

use Illuminate\Routing\Controller;
use Illuminate\Http\Request;
use Modules\Produccion\Entities\ProdVariety;
// Asegúrate que el modelo se llame igual que el archivo en Entities (Variety o ProdVariety)

class VarietyController extends Controller
{
    public function index()
    {
        // Retornamos lista para el <select> del frontend
        return response()->json(ProdVariety::all());
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            // Recordamos tu enum: SEED, GRAFT, VEGETATIVE
            'type' => 'required|in:SEED,GRAFT,VEGETATIVE',
            'scientific_name' => 'nullable|string'
        ]);

        $variety = ProdVariety::create([
            'name' => $request->name,
            'type' => $request->type,
            'scientific_name' => $request->scientific_name
        ]);

        return response()->json($variety, 201);
    }
}
