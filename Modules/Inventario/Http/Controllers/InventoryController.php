<?php

namespace Modules\Inventario\Http\Controllers;

use Illuminate\Routing\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Modules\Inventario\Entities\Machinery;
use Modules\Inventario\Entities\Product;
use Modules\Inventario\Entities\Batch;

class InventoryController extends Controller
{
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

	public function getDashboardStats()
    {
        // 1. TARJETAS SUPERIORES (Conteos Rápidos)
        $productsCount = Product::count();
        $machineryCount = Machinery::where('type', 'VEHICLE')->count();
        $toolsCount = Machinery::where('type', 'TOOL')->count();

        // Valorización (Sigue siendo útil para reportes a contraloría aunque sea gobierno)
        $totalValue = Batch::where('current_quantity', '>', 0)
            ->sum(DB::raw('current_quantity * unit_cost'));

        // 2. ÚLTIMOS MOVIMIENTOS (Traer de actividades recientes)
        $recentMovements = DB::table('p_activity_products as pivot')
            ->join('p_activities as act', 'pivot.activity_id', '=', 'act.id')
            ->join('inv_products as prod', 'pivot.product_id', '=', 'prod.id')
            ->leftJoin('prod_batches as batch', 'act.prod_batch_id', '=', 'batch.id') // Para saber si fue Vivero/Campo
            ->leftJoin('p_fields as field', 'act.field_id', '=', 'field.id')
            ->select(
                'prod.name as product_name',
                'pivot.quantity',
                'prod.unit',
                'act.activity_date',
                'batch.environment', // NURSERY o FIELD
                'field.name as field_name'
            )
            ->orderBy('act.activity_date', 'desc')
            ->limit(5)
            ->get();

        // 3. GRÁFICO COMPARATIVO (Consumo por Entorno últimos 6 meses)
        $consumptionStats = DB::table('p_activity_products as pivot')
            ->join('p_activities as act', 'pivot.activity_id', '=', 'act.id')
            ->join('prod_batches as batch', 'act.prod_batch_id', '=', 'batch.id')
            ->select(
            // CORRECCIÓN: Usar TO_CHAR en lugar de DATE_FORMAT para Postgres
                DB::raw("TO_CHAR(act.activity_date, 'YYYY-MM') as month"),
                'batch.environment',
                DB::raw('count(*) as total_tasks'),
                DB::raw('sum(pivot.total_cost) as estimated_cost')
            )
            ->where('act.activity_date', '>=', now()->subMonths(6))
            ->groupBy('month', 'batch.environment')
            ->orderBy('month', 'asc')
            ->get();

        // 4. ALERTA DE CADUCIDAD (Semáforo)
        $expiringBatches = Batch::where('current_quantity', '>', 0)
            ->where('expiration_date', '<=', now()->addDays(60))
            ->with('product')
            ->orderBy('expiration_date', 'asc')
            ->take(5)
            ->get();

        return response()->json([
            'cards' => [
                'products' => $productsCount,
                'machinery' => $machineryCount,
                'tools' => $toolsCount,
                'valuation' => round($totalValue, 2)
            ],
            'recent_movements' => $recentMovements,
            'chart_data' => $consumptionStats,
            'expiring_items' => $expiringBatches
        ]);
    }
}
