<?php

namespace Modules\Campo\Http\Controllers;

use Illuminate\Routing\Controller;
use Illuminate\Http\Request;
use Modules\Campo\Entities\Field;

class FieldController extends Controller
{
    // 1. Listar Lotes (Para el Select del formulario)
    public function index()
    {
        return response()->json(
            Field::where('is_active', true)->orderBy('name')->get()
        );
    }

    // 2. Crear Nuevo Lote (Ej: "Lote A - Cacao")
    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'area_hectares' => 'required|numeric|min:0.1',
            'current_crop' => 'nullable|string' // Ej: Maíz, Cacao, Pasto
        ]);

        $field = Field::create($data);

        return response()->json($field, 201);
    }

    // 3. Actualizar (Si cambió el cultivo o corriges el área)
    public function update(Request $request, $id)
    {
        $field = Field::findOrFail($id);

        $data = $request->validate([
            'name' => 'string|max:255',
            'area_hectares' => 'numeric|min:0.1',
            'current_crop' => 'nullable|string'
        ]);

        $field->update($data);

        return response()->json($field);
    }

    // 4. Eliminar (Soft Delete lógico para no romper historial)
    public function destroy($id)
    {
        $field = Field::findOrFail($id);
        // No borramos físicamente, solo lo desactivamos para que no salga en nuevos registros
        $field->update(['is_active' => false]);

        return response()->json(['message' => 'Lote desactivado correctamente']);
    }

}
