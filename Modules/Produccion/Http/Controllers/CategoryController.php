<?php

namespace Modules\Produccion\Http\Controllers;

use Illuminate\Routing\Controller;
use Illuminate\Http\Request;
use Modules\Produccion\Entities\ProdCategory;

class CategoryController extends Controller
{
    public function index(Request $request)
    {
        // Permite filtrar: /api/categories?type=semilla
        $query = ProdCategory::query();

        if ($request->has('type')) {
            $query->where('type', $request->type);
        }

        return response()->json($query->get());
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string',
            'type' => 'required|string' // Ej: 'semilla', 'variedad'
        ]);

        $category = ProdCategory::create($request->all());
        return response()->json($category, 201);
    }
}
