<?php

namespace App\Modules\Planificacion\Http\Controllers;
use App\Http\Controllers\Controller;
use App\Modules\Planificacion\Models\Material;
use Illuminate\Http\Request;

class MaterialController extends Controller
{
    /**
     * Devuelve una lista de materiales en formato JSON.
     */
    public function index()
    {
        // En lugar de view(), devolvemos json()
        return response()->json(Material::latest()->get());
    }

    /**
     * Guarda un nuevo material y devuelve el registro creado en JSON.
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255|unique:materials,name',
        ]);

        $material = Material::create($request->all());

        // Devolvemos el nuevo material con un código de estado 201 (Created)
        return response()->json($material, 201);
    }

    /**
     * Devuelve un material específico en JSON.
     */
    public function show(Material $material)
    {
        return response()->json($material);
    }

    /**
     * Actualiza un material y devuelve el registro actualizado en JSON.
     */
    public function update(Request $request, Material $material)
    {
        $request->validate([
            'name' => 'required|string|max:255|unique:materials,name,' . $material->id,
        ]);

        $material->update($request->all());

        return response()->json($material);
    }

    /**
     * Elimina (soft delete) un material y devuelve una respuesta vacía.
     */
    public function destroy(Material $material)
    {
        $material->delete();

        // Devolvemos una respuesta vacía con código 204 (No Content)
        return response()->json(null, 204);
    }
}
