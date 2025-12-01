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
        $products = Product::with(['category', 'batches' => function($q) {
            $q->where('current_quantity', '>', 0)
                ->orderBy('expiration_date', 'asc');
        }])->get();

        $products->each(function($p) {
            $p->total_stock_calculated = $p->total_stock;
        });

        return response()->json($products);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string',
            'active_ingredient' => 'nullable|string',
            'unit' => 'required|string',
            'category_id' => 'required|integer',
            'min_stock' => 'integer'
        ]);

        $product = Product::create($validated);
        return response()->json($product);
    }

    public function addBatch(Request $request, $productId)
    {
        $request->validate([
            'batch_code' => 'required|string',
            'entry_date' => 'required|date',
            'expiration_date' => 'required|date',
            'quantity' => 'required|numeric|min:0.1',
            'unit_cost' => 'required|numeric|min:0',
        ]);

        $batch = Batch::create([
            'product_id' => $productId,
            'batch_code' => $request->batch_code,
            'entry_date' => $request->entry_date,
            'expiration_date' => $request->expiration_date,
            'unit_cost' => $request->unit_cost,
            'initial_quantity' => $request->quantity,
            'current_quantity' => $request->quantity
        ]);

        return response()->json($batch);
    }
}
