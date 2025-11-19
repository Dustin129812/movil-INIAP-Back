<?php

namespace Modules\Inventario\Http\Controllers;

use Illuminate\Routing\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Modules\Inventario\Entities\Product;
use Modules\Inventario\Entities\Batch;

class InventoryController extends Controller
{
    // Resumen de Stock para el Dashboard
    public function stockSummary()
    {
        $products = Product::with(['batches' => function($q) {
            $q->where('current_quantity', '>', 0);
        }])->get();

        // Mapeamos para enviar una estructura limpia al front
        $data = $products->map(function($p) {
            return [
                'id' => $p->id,
                'name' => $p->name,
                'unit' => $p->unit,
                'total_stock' => $p->total_stock,
                'batches_count' => $p->batches->count(), // Cuantos lotes activos tiene
                'average_cost' => $p->batches->avg('unit_cost') ?? 0 // Costo promedio referencial
            ];
        });

        return response()->json($data);
    }

    // Acción de COMPRA: Agrega stock creando un nuevo lote
    public function addBatch(Request $request, $productId)
    {
        $request->validate([
            'batch_code' => 'required|string',
            'quantity' => 'required|numeric|min:0.1',
            'unit_cost' => 'required|numeric|min:0',
            'expiration_date' => 'required|date',
        ]);

        try {
            $batch = Batch::create([
                'product_id' => $productId,
                'batch_code' => $request->batch_code,
                'initial_quantity' => $request->quantity,
                'current_quantity' => $request->quantity, // Al inicio es igual
                'unit_cost' => $request->unit_cost,
                'expiration_date' => $request->expiration_date,
                'is_active' => true
            ]);

            return response()->json([
                'message' => 'Lote ingresado correctamente',
                'data' => $batch
            ], 201);

        } catch (\Exception $e) {
            return response()->json(['error' => 'Error al ingresar lote: ' . $e->getMessage()], 500);
        }
    }

    public function index()
    {
        return Product::with('category')->get();
    }

    public function store(Request $request)
    {
        // Lógica simple para crear el producto catálogo (nombre, unidad)
        $validated = $request->validate([
            'name' => 'required',
            'category_id' => 'required',
            'unit' => 'required'
        ]);
        return Product::create($validated);
    }

    /**
     * REGISTRAR SALIDA / CONSUMO (Lógica FEFO)
     * Busca los lotes más viejos y descuenta de ahí hasta completar la cantidad.
     */
    public function consumeProduct(Request $request, $productId)
    {
        $request->validate([
            'quantity' => 'required|numeric|min:0.01',
            'reason'   => 'nullable|string' // Ej: "Aplicación en Lote 3"
        ]);

        $quantityNeeded = $request->quantity;
        $product = Product::findOrFail($productId);

        // 1. Verificar Stock Total
        if ($product->total_stock < $quantityNeeded) {
            return response()->json([
                'error' => "Stock insuficiente. Tienes {$product->total_stock} {$product->unit}, intentas usar {$quantityNeeded}."
            ], 400);
        }

        // 2. Traer lotes activos ordenados por FECHA CADUCIDAD (Lo más viejo primero)
        $batches = $product->batches()
            ->where('current_quantity', '>', 0)
            ->where('is_active', true)
            ->orderBy('expiration_date', 'asc')
            ->get();

        $costoTotalSalida = 0;
        $lotesAfectados = [];

        DB::beginTransaction();
        try {
            foreach ($batches as $batch) {
                if ($quantityNeeded <= 0) break; // Ya terminamos

                // ¿Cuánto tomamos de este lote?
                // Lo que tenga el lote O lo que necesitemos (el menor de los dos)
                $take = min($batch->current_quantity, $quantityNeeded);

                // Cálculos
                $costoTotalSalida += ($take * $batch->unit_cost);

                // Actualizar Lote
                $batch->current_quantity -= $take;
                $batch->save();

                // Registrar traza (Opcional pero recomendado para tu debug)
                $lotesAfectados[] = [
                    'batch_code' => $batch->batch_code,
                    'taken' => $take,
                    'cost' => $batch->unit_cost
                ];

                // Reducir lo que nos falta
                $quantityNeeded -= $take;
            }

            DB::commit();

            return response()->json([
                'message' => 'Salida registrada correctamente',
                'total_cost' => $costoTotalSalida, // Dato clave para tu futuro módulo de Costos
                'affected_batches' => $lotesAfectados
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['error' => 'Error procesando salida'], 500);
        }
    }

    // ... (tus otras funciones)

    public function getDashboardStats()
    {
        $totalValue = Batch::where('is_active', true)
            ->where('current_quantity', '>', 0)
            ->get()
            ->sum(function($batch) {
                return $batch->current_quantity * $batch->unit_cost;
            });

        $lowStockProducts = Product::get()->filter(function($p) {
            return $p->total_stock <= $p->min_stock;
        })->values(); // values() para reindexar el array JSON

        $expiringBatches = Batch::where('is_active', true)
            ->where('current_quantity', '>', 0)
            ->whereDate('expiration_date', '<=', now()->addDays(90))
            ->orderBy('expiration_date', 'asc')
            ->with('product') // Para saber el nombre
            ->take(10) // Solo los 10 más urgentes
            ->get();

        return response()->json([
            'total_inventory_value' => round($totalValue, 2),
            'low_stock_count' => $lowStockProducts->count(),
            'expiring_count' => $expiringBatches->count(),
            'low_stock_items' => $lowStockProducts->take(5), // Top 5 alertas
            'expiring_items' => $expiringBatches
        ]);
    }
}
