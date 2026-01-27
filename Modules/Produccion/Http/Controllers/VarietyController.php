<?php

namespace Modules\Produccion\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Produccion\Entities\ProdVariety;
use Illuminate\Support\Facades\DB;

class VarietyController extends Controller
{
    // Listar variedades con todas sus relaciones para la tabla
    public function index()
    {
        return ProdVariety::with(['productive_rubro', 'crop', 'category', 'variety_type'])
            ->orderBy('name', 'asc')
            ->get();
    }

    // LÓGICA CORE: Guardar sistematizado
    public function store(Request $request)
    {
        // 1. Validamos que lleguen los 4 pilares
        $validated = $request->validate([
            'productive_rubro_id' => 'required|exists:productive_rubros,id',
            'crop_id'             => 'nullable|exists:crops,id', // Opcional si el rubro es genérico
            'category_id'         => 'required|exists:categories,id',
            'variety_type_id'     => 'required|exists:variety_types,id',
            'name'                => 'required|string|max:255',
        ]);

        // 2. Validación de Unicidad Compuesta (Evitar duplicados exactos)
        // Buscamos si ya existe esta combinación exacta
        $exists = ProdVariety::where('productive_rubro_id', $request->productive_rubro_id)
            ->where('category_id', $request->category_id)
            ->where('variety_type_id', $request->variety_type_id)
            ->where('name', $request->name) // ej: "Superchola"
            ->exists();

        if ($exists) {
            return response()->json(['message' => 'Esta variedad ya existe con esa configuración exacta.'], 422);
        }

        // 3. Crear la Variedad
        $variety = ProdVariety::create([
            'productive_rubro_id' => $request->productive_rubro_id,
            'crop_id'             => $request->crop_id ?? $this->getDefaultCrop($request->productive_rubro_id), // Fallback inteligente
            'category_id'         => $request->category_id,
            'variety_type_id'     => $request->variety_type_id,
            'name'                => strtoupper($request->name), // Estandarizar a mayúsculas
        ]);

        return response()->json($variety, 201);
    }

    private function getDefaultCrop($rubroId) {
        return \DB::table('crops')->where('productive_rubro_id', $rubroId)->value('id');
    }
}
