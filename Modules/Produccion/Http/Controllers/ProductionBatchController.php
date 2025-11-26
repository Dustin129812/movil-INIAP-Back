<?php

namespace Modules\Produccion\Http\Controllers;

use Illuminate\Routing\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Modules\Produccion\Entities\ProductionBatch;
use Modules\Produccion\Entities\ProdProtocol;

class ProductionBatchController extends Controller
{
    public function index()
    {
        $batches = ProductionBatch::with(['protocol.variety'])
            ->where('status', '!=', 'CANCELED')
            ->get();

        $batches->each(function($batch) {
            $batch->real_accumulated_cost = $batch->real_cost;
            $batch->real_unit_cost = $batch->unit_cost;
        });

        return response()->json(
            ProductionBatch::with(['protocol.variety', 'field'])
                ->where('status', '!=', 'CANCELED')
                ->get()
        );
    }

    // 2. CREAR LOTE (El momento de la verdad)
    public function store(Request $request)
    {
        $request->validate([
            'protocol_id' => 'required|exists:prod_protocols,id',
            'field_id' => 'nullable|exists:p_fields,id', // <--- NUEVO
            'start_date' => 'required|date',
            'initial_quantity' => 'required|integer|min:1',
            'batch_code' => 'required|string|unique:prod_batches,batch_code',
            'environment' => 'required|in:NURSERY,FIELD' // <--- NUEVO
        ]);

        $protocol = ProdProtocol::findOrFail($request->protocol_id);
        $endDate = Carbon::parse($request->start_date)->addDays($protocol->estimated_days);

        $batch = ProductionBatch::create([
            'batch_code' => $request->batch_code,
            'protocol_id' => $protocol->id,
            'field_id' => $request->field_id,
            'environment' => $request->environment,
            'start_date' => $request->start_date,
            'estimated_end_date' => $endDate,
            'initial_quantity' => $request->initial_quantity,
            'current_quantity' => $request->initial_quantity,
            'status' => 'IN_PROGRESS',
            'current_stage' => 'Inicio'
        ]);

        return response()->json($batch, 201);
    }

    /**
     * 3. EL CEREBRO: Generar Planificación Sugerida
     * Este método es el que llamará tu Frontend en el módulo de "Planificación Semanal"
     * para saber qué toca hacer esta semana.
     */
    public function getSuggestedActivities(Request $request, $id)
    {
        $batch = ProductionBatch::with('protocol.details')->findOrFail($id);

        // Factor de escala: Si la receta es para 10k y hago 5k, el factor es 0.5
        // Usamos current_quantity para ajustar si hubo mortalidad
        $scaleFactor = $batch->current_quantity / $batch->protocol->base_quantity;

        $startDate = Carbon::parse($batch->start_date);

        // Rango de fechas solicitado (ej: esta semana)
        $weekStart = $request->query('start_date') ? Carbon::parse($request->query('start_date')) : Carbon::now()->startOfWeek();
        $weekEnd = $request->query('end_date') ? Carbon::parse($request->query('end_date')) : Carbon::now()->endOfWeek();

        $suggestions = [];

        foreach ($batch->protocol->details as $detail) {
            // Calculamos la fecha teórica: Inicio del lote + día del protocolo
            $activityDate = $startDate->copy()->addDays($detail->day_start - 1);

            // Si la actividad cae dentro de la semana que estamos planificando
            if ($activityDate->between($weekStart, $weekEnd)) {

                // Calculamos la cantidad real necesaria (Regla de tres)
                $realQuantity = $detail->quantity * $scaleFactor;

                $suggestions[] = [
                    'date' => $activityDate->toDateString(),
                    'stage' => $detail->stage,
                    'task' => $detail->task,
                    'resource_type' => $detail->resource_type,

                    // IDs para que tu frontend pre-llene el formulario
                    'inv_product_id' => $detail->inv_product_id,
                    'inv_machinery_id' => $detail->inv_machinery_id,
                    'resource_name' => $detail->resource_name,

                    // Cantidad ajustada a la realidad del lote
                    'suggested_quantity' => round($realQuantity, 4),
                    'batch_code' => $batch->batch_code
                ];
            }
        }

        return response()->json($suggestions);
    }

    /**
     * REPORTE FINANCIERO DEL LOTE
     * Responde: ¿Cuánto me costó producir cada Kilo?
     */
    public function getBatchFinancialReport($id)
    {
        $batch = ProductionBatch::findOrFail($id);

        // 1. Sumar todos los Costos (Inputs)
        // Buscamos todas las actividades de este lote
        $activities = \Modules\Campo\Entities\Activity::where('prod_batch_id', $id)
            ->with(['products', 'machinery'])
            ->get();

        $totalLaborCost = $activities->sum('labor_cost_total');

        $totalProductCost = $activities->flatMap->products->sum('total_cost');

        $totalMachineryCost = $activities->flatMap->machinery->sum('total_cost');

        $grandTotalCost = $totalLaborCost + $totalProductCost + $totalMachineryCost;

        // 2. Sumar toda la Producción (Outputs)
        $totalHarvested = DB::table('p_harvests')
            ->where('prod_batch_id', $id)
            ->sum('quantity'); // Asumimos que todo está en la misma unidad (ej: kg)

        // 3. Cálculo de KPIs
        $unitCost = 0;
        if ($totalHarvested > 0) {
            $unitCost = $grandTotalCost / $totalHarvested;
        }

        return response()->json([
            'batch_code' => $batch->batch_code,
            'status' => $batch->status,
            'financials' => [
                'total_investment' => round($grandTotalCost, 2),
                'breakdown' => [
                    'labor' => round($totalLaborCost, 2),
                    'inputs' => round($totalProductCost, 2),
                    'machinery' => round($totalMachineryCost, 2),
                ],
                'production' => [
                    'total_quantity' => round($totalHarvested, 2),
                    'unit' => 'kg', // Podrías hacerlo dinámico
                ],
                'kpi' => [
                    'unit_production_cost' => round($unitCost, 2) // Ej: $0.45 por kilo
                ]
            ]
        ]);
    }
}
