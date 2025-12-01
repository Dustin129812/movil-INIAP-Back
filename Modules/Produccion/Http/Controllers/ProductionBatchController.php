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
        // 1. Obtenemos los lotes
        $batches = ProductionBatch::with(['protocol.variety', 'field'])
            ->where('status', '!=', 'CANCELED')
            ->orderBy('created_at', 'desc') // Ordenar por más reciente
            ->get();

        // 2. Iteramos para calcular el costo real acumulado (Igual que en el Dashboard)
        $batches->transform(function($batch) {

            // A. Suma Mano de Obra
            $labor = DB::table('p_activities')
                ->where('prod_batch_id', $batch->id)
                ->where('status', '!=', 'cancelled')
                ->sum('labor_cost_total');

            // B. Suma Insumos
            $products = DB::table('p_activity_products')
                ->join('p_activities', 'p_activity_products.activity_id', '=', 'p_activities.id')
                ->where('p_activities.prod_batch_id', $batch->id)
                ->where('p_activities.status', '!=', 'cancelled')
                ->sum('p_activity_products.total_cost');

            // C. Suma Maquinaria
            $machinery = DB::table('p_activity_machinery')
                ->join('p_activities', 'p_activity_machinery.activity_id', '=', 'p_activities.id')
                ->where('p_activities.prod_batch_id', $batch->id)
                ->where('p_activities.status', '!=', 'cancelled')
                ->sum('p_activity_machinery.total_cost');

            $batch->real_accumulated_cost = round($labor + $products + $machinery, 2);

            return $batch;
        });

        return response()->json($batches);
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
    /**
     * REPORTE FINANCIERO DETALLADO (KARDEX DE COSTOS)
     * Calcula KPIs: Costo Total, Costo Unitario (Yield), Costo por Área.
     */
    public function getBatchFinancialReport($id)
    {
        $batch = ProductionBatch::with(['field', 'protocol', 'protocol.variety'])->findOrFail($id);

        // 1. OBTENER TODAS LAS ACTIVIDADES Y SUS GASTOS
        $activities = \Modules\Campo\Entities\Activity::where('prod_batch_id', $id)
            ->where('status', '!=', 'cancelled')
            ->with(['products.product', 'machinery.machine'])
            ->orderBy('activity_date', 'asc')
            ->get();

        // 2. CALCULAR TOTALES Y PREPARAR DETALLES
        $totalLabor = 0;
        $totalInputs = 0;
        $totalMachinery = 0;

        $detailsInputs = [];
        $detailsMachinery = [];
        $detailsLabor = [];

        foreach ($activities as $act) {
            // A. Mano de Obra
            if ($act->labor_cost_total > 0) {
                $totalLabor += $act->labor_cost_total;
                $detailsLabor[] = [
                    'date' => $act->activity_date,
                    'task' => $act->task_type,
                    'workers' => $act->workers_count,
                    'cost' => $act->labor_cost_total
                ];
            }

            // B. Insumos (Agrupamos por si se repiten productos en diferentes fechas)
            foreach ($act->products as $pivot) {
                $cost = $pivot->total_cost; // Ya calculado en store/update
                $totalInputs += $cost;

                $detailsInputs[] = [
                    'date' => $act->activity_date,
                    'product' => $pivot->product->name, // Nombre producto
                    'quantity' => $pivot->quantity,
                    'unit' => $pivot->product->unit,
                    'unit_cost' => $pivot->historical_unit_cost,
                    'total' => $cost
                ];
            }

            // C. Maquinaria
            foreach ($act->machinery as $pivot) {
                $cost = $pivot->total_cost;
                $totalMachinery += $cost;

                $detailsMachinery[] = [
                    'date' => $act->activity_date,
                    'machine' => $pivot->machine->name,
                    'usage' => $pivot->hours_or_km, // Horas o Km
                    'unit_cost' => $pivot->historical_hourly_cost,
                    'total' => $cost
                ];
            }
        }

        $grandTotalCost = $totalLabor + $totalInputs + $totalMachinery;

        // 3. DATOS DE PRODUCCIÓN (COSECHA)
        $harvests = DB::table('p_harvests')->where('prod_batch_id', $id)->get();
        $totalHarvestedQty = $harvests->sum('quantity');
        // Nota: Asumimos homogeneidad en unidades (todo kg o todo lb).
        // Si mezclas unidades, necesitarías una tabla de conversión.
        $harvestUnit = $harvests->first()->unit ?? 'Unidades';

        // 4. DATOS DE TERRENO (ÁREA)
        $area = $batch->field ? $batch->field->area_hectares : 0;
        $areaUnit = ($batch->environment === 'NURSERY') ? 'm²' : 'Ha';

        // 5. CÁLCULO DE KPIS (INDICADORES CLAVE)

        // Costo Unitario de Producción (Ej: Cuánto costó producir 1 Kg)
        $unitCost = ($totalHarvestedQty > 0) ? ($grandTotalCost / $totalHarvestedQty) : 0;

        // Costo por Unidad de Tierra (Ej: Cuánto he gastado por Hectárea)
        $areaCost = ($area > 0) ? ($grandTotalCost / $area) : 0;

        return response()->json([
            'meta' => [
                'batch_code' => $batch->batch_code,
                'variety' => $batch->protocol->variety->name ?? 'N/A',
                'field_name' => $batch->field->name ?? 'Sin Asignar',
                'area' => $area,
                'area_unit' => $areaUnit,
                'status' => $batch->status,
                'start_date' => $batch->start_date
            ],
            'financials' => [
                'total_investment' => round($grandTotalCost, 2),
                'breakdown' => [
                    'labor' => round($totalLabor, 2),
                    'inputs' => round($totalInputs, 2),
                    'machinery' => round($totalMachinery, 2),
                ],
                'production' => [
                    'total_harvested' => round($totalHarvestedQty, 2),
                    'unit' => $harvestUnit,
                    'harvest_count' => $harvests->count()
                ],
                'kpi' => [
                    'cost_per_product_unit' => round($unitCost, 2), // $ / kg
                    'cost_per_land_unit' => round($areaCost, 2)     // $ / Ha
                ]
            ],
            'details' => [
                'inputs' => $detailsInputs,
                'machinery' => $detailsMachinery,
                'labor' => $detailsLabor
            ]
        ]);
    }

    public function getGlobalStats()
    {
        // 1. Traer lotes activos
        $activeBatches = ProductionBatch::where('status', 'IN_PROGRESS')->get();

        $totalInvestment = 0;
        $batchesByStage = [];

        // Iteramos para calcular el costo real de cada lote
        foreach($activeBatches as $batch) {

            // A. Suma Mano de Obra (Tabla Activities)
            $labor = DB::table('p_activities')
                ->where('prod_batch_id', $batch->id)
                ->where('status', '!=', 'cancelled')
                ->sum('labor_cost_total');

            // B. Suma Insumos (Tabla Pivote Productos)
            $products = DB::table('p_activity_products')
                ->join('p_activities', 'p_activity_products.activity_id', '=', 'p_activities.id')
                ->where('p_activities.prod_batch_id', $batch->id)
                ->where('p_activities.status', '!=', 'cancelled')
                ->sum('p_activity_products.total_cost');

            // C. Suma Maquinaria (Tabla Pivote Maquinaria)
            $machinery = DB::table('p_activity_machinery')
                ->join('p_activities', 'p_activity_machinery.activity_id', '=', 'p_activities.id')
                ->where('p_activities.prod_batch_id', $batch->id)
                ->where('p_activities.status', '!=', 'cancelled')
                ->sum('p_activity_machinery.total_cost');

            $batchCost = $labor + $products + $machinery;

            // Guardamos el costo calculado en el objeto para poder ordenarlo luego
            $batch->calculated_total_cost = round($batchCost, 2);

            $totalInvestment += $batchCost;

            // Conteo por fases
            $stage = $batch->current_stage ?? 'Inicio';
            if(!isset($batchesByStage[$stage])) $batchesByStage[$stage] = 0;
            $batchesByStage[$stage]++;
        }

        // 2. Estructura de Gastos Global (De todos los activos)
        $batchIds = $activeBatches->pluck('id');

        $globalBreakdown = [
            'Labor' => DB::table('p_activities')
                ->whereIn('prod_batch_id', $batchIds)
                ->where('status', '!=', 'cancelled')
                ->sum('labor_cost_total'),

            'Insumos' => DB::table('p_activity_products')
                ->join('p_activities', 'p_activity_products.activity_id', '=', 'p_activities.id')
                ->whereIn('p_activities.prod_batch_id', $batchIds)
                ->where('p_activities.status', '!=', 'cancelled')
                ->sum('p_activity_products.total_cost'),

            'Maquinaria' => DB::table('p_activity_machinery')
                ->join('p_activities', 'p_activity_machinery.activity_id', '=', 'p_activities.id')
                ->whereIn('p_activities.prod_batch_id', $batchIds)
                ->where('p_activities.status', '!=', 'cancelled')
                ->sum('p_activity_machinery.total_cost'),
        ];

        // Ordenar los lotes por costo (el más caro primero) para la tabla resumen
        $sortedBatches = $activeBatches->sortByDesc('calculated_total_cost')->values()->take(5);

        return response()->json([
            'total_active_investment' => round($totalInvestment, 2),
            'active_batches_count' => $activeBatches->count(),
            'batches_by_stage' => $batchesByStage,
            'cost_structure' => $globalBreakdown,
            'top_expensive_batches' => $sortedBatches
        ]);
    }
}
