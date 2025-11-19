<?php

namespace Modules\Inventario\Http\Controllers;

use Illuminate\Contracts\Support\Renderable;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Inventario\Entities\Batch;
use Modules\Inventario\Entities\Product;

class ProductController extends Controller
{
    public function index()
    {
        // Traemos productos con sus lotes activos ordenados por fecha de caducidad (FEFO)
        $products = Product::with(['category', 'batches' => function($q) {
            $q->where('current_quantity', '>', 0)
                ->orderBy('expiration_date', 'asc');
        }])->get();

        // Agregamos el total calculado
        $products->each(function($p) {
            $p->total_stock_calculated = $p->total_stock;
        });

        return response()->json($products);
    }

    public function store(Request $request)
    {
        // Crear Producto (Catálogo)
        $product = Product::create($request->validate([
            'name' => 'required|string',
            'unit' => 'required|string', // kg, lt
            'category_id' => 'required|integer', // Asumiendo que tienes categorías
            'min_stock' => 'integer'
        ]));
        return response()->json($product);
    }

    public function addBatch(Request $request, $productId)
    {
        // Agregar Stock (Nuevo Lote)
        $batch = Batch::create([
            'product_id' => $productId,
            'batch_code' => $request->batch_code,
            'expiration_date' => $request->expiration_date, //
            'unit_cost' => $request->unit_cost, // [cite: 27]
            'initial_quantity' => $request->quantity,
            'current_quantity' => $request->quantity
        ]);

        return response()->json($batch);
    }
}
