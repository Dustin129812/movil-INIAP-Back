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

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'type' => 'required|in:OPEN_FIELD,FACILITY',
            'area_hectares' => 'required|numeric|min:0.01',
            'current_crop' => 'nullable|string'
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
            'type' => 'in:OPEN_FIELD,FACILITY',
            'area_hectares' => 'numeric|min:0.01',
            'current_crop' => 'nullable|string'
        ]);

        $field->update($data);

        return response()->json($field);
    }

    public function destroy($id)
    {
        $field = Field::findOrFail($id);
        $field->update(['is_active' => false]);

        return response()->json(['message' => 'Lote desactivado correctamente']);
    }

}
