<?php

namespace Modules\Inventario\Http\Controllers;

use Illuminate\Routing\Controller;
use Illuminate\Http\Request;
use Modules\Inventario\Entities\Category; // Asegúrate de tener el Modelo ProdCategory creado (si no, avísame)

class CategoryController extends Controller
{
    // 1. Listar todas las categorías para el "Select" del formulario
    public function index()
    {
        return response()->json(Category::all());
    }

    // 2. Crear una nueva categoría (Ej: "Fungicidas")
    public function store(Request $request)
    {
        $request->validate(['name' => 'required|string|unique:inv_categories,name']);

        $category = Category::create([
            'name' => $request->name
        ]);

        return response()->json($category, 201);
    }

    // 3. Eliminar (Opcional, por si te equivocas)
    public function destroy($id)
    {
        $category = Category::findOrFail($id);
        // Aquí podrías validar que no tenga productos asociados antes de borrar
        $category->delete();
        return response()->json(['message' => 'Categoría eliminada']);
    }
}
