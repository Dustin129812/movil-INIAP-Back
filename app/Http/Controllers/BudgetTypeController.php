<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Budget_Type;
use Illuminate\Http\Request;


class BudgetTypeController extends Controller
{
    // 1. LEER (Listar todos)
    public function index()
    {
        // Retorna todos los tipos de presupuesto
        return Budget_Type::all();
    }

    // 2. CREAR (Guardar nuevo)
    public function store(Request $request)
    {
        // Validación (El nombre es obligatorio y único)
        $request->validate([
            'name' => 'required|string|unique:budget_types,name'
        ]);

        // Crea el registro usando el $fillable que ya tienes en tu modelo
        $budgetType = Budget_Type::create($request->all());

        return response()->json([
            'message' => 'Creado exitosamente',
            'data' => $budgetType
        ], 201);
    }

    // 3. LEER UNO (Buscar por ID)
    public function show($id)
    {
        $budgetType = Budget_Type::find($id);

        if (!$budgetType) {
            return response()->json(['message' => 'No encontrado'], 404);
        }

        return $budgetType;
    }

    // 4. ACTUALIZAR
    public function update(Request $request, $id)
    {
        $budgetType = Budget_Type::find($id);

        if (!$budgetType) {
            return response()->json(['message' => 'No encontrado'], 404);
        }

        // Validación (ignora el ID actual para que no de error de "ya existe" si no cambias el nombre)
        $request->validate([
            'name' => 'required|string|unique:budget_types,name,' . $id
        ]);

        $budgetType->update($request->all());

        return response()->json([
            'message' => 'Actualizado exitosamente',
            'data' => $budgetType
        ]);
    }

    // 5. ELIMINAR
    public function destroy($id)
    {
        $budgetType = Budget_Type::find($id);

        if (!$budgetType) {
            return response()->json(['message' => 'No encontrado'], 404);
        }

        $budgetType->delete();

        return response()->json(['message' => 'Eliminado exitosamente']);
    }
}