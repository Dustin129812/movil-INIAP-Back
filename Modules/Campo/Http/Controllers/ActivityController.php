<?php

namespace Modules\Campo\Http\Controllers;

use App\Modules\Planificacion\Models\WeekActivity;
use Carbon\Carbon;
use Illuminate\Routing\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Modules\Campo\Entities\Activity;
use Modules\Inventario\Entities\Product;
use Modules\Inventario\Entities\Batch;
use Modules\Inventario\Entities\Machinery;

class ActivityController extends Controller
{
    /**
     * Listar actividades (Historial del Libro de Campo)
     */
    public function index(Request $request)
    {
        // Cargamos relaciones para mostrar: Lote, Productos usados y Maquinaria
        $query = Activity::with(['field', 'products.product', 'machinery.machine'])
            ->orderBy('activity_date', 'desc');

        // Filtro opcional por Lote (si quieres ver solo historia del Lote Norte)
        if ($request->has('field_id')) {
            $query->where('field_id', $request->field_id);
        }

        return response()->json($query->paginate(20));
    }

    /**
     * GUARDAR ACTIVIDAD (La lógica compleja)
     */
    public function store(Request $request)
    {
        // 1. Validación Estricta
        $request->validate([
            'field_id' => 'required|exists:p_fields,id',
            'activity_date' => 'required|date',
            'task_type' => 'required|string', // Ej: "Fumigación"
            'extra_data' => 'nullable|array',

            // Mano de Obra (Opcionales pero recomendados)
            'workers_count' => 'numeric|min:0',
            'labor_hours' => 'numeric|min:0',
            'labor_cost_total' => 'numeric|min:0',

            // Arrays de Insumos y Maquinaria
            'products' => 'array',
            'products.*.product_id' => 'required|exists:inv_products,id',
            'products.*.quantity' => 'required|numeric|min:0.01',

            'machinery' => 'array',
            'machinery.*.machinery_id' => 'required|exists:inv_machinery,id',
            'machinery.*.hours_or_km' => 'required|numeric|min:0.01',
        ]);

        // INICIO DE TRANSACCIÓN (Todo o Nada)
        DB::beginTransaction();

        try {
            // 2. Crear la Cabecera (La Actividad)
            $activity = Activity::create([
                'week_activity_id' => $request->week_activity_id,
                'field_id' => $request->field_id,
                'activity_date' => $request->activity_date,
                'task_type' => $request->task_type,
                'observation' => $request->observation,
                'workers_count' => $request->workers_count ?? 0,
                'labor_hours' => $request->labor_hours ?? 0,
                'labor_cost_total' => $request->labor_cost_total ?? 0,
                'extra_data' => $request->extra_data,
                'status' => 'completed'
            ]);

            // 3. PROCESAR INSUMOS (Lógica FEFO - First Expired, First Out)
            if ($request->has('products')) {
                foreach ($request->products as $item) {
                    $product = Product::find($item['product_id']);
                    $qtyNeeded = $item['quantity'];

                    // Validar Stock Total
                    if ($product->total_stock < $qtyNeeded) {
                        throw new \Exception("Stock insuficiente para {$product->name}. Tienes {$product->total_stock}, necesitas {$qtyNeeded}.");
                    }

                    // Buscar lotes ordenados por caducidad
                    $batches = $product->batches()
                        ->where('current_quantity', '>', 0)
                        ->where('is_active', true)
                        ->orderBy('expiration_date', 'asc')
                        ->get();

                    $acumulatedCost = 0;

                    // Bucle de descuento
                    foreach ($batches as $batch) {
                        if ($qtyNeeded <= 0) break;

                        $take = min($batch->current_quantity, $qtyNeeded);

                        // Restar del lote
                        $batch->current_quantity -= $take;
                        $batch->save();

                        // Sumar costo para el registro histórico
                        $acumulatedCost += ($take * $batch->unit_cost);
                        $qtyNeeded -= $take;
                    }

                    // Calcular el costo unitario promedio de ESTA aplicación
                    // (Puede que usaras un lote barato y uno caro mezclados)
                    $historicalUnitCost = $acumulatedCost / $item['quantity'];

                    // Guardar en la tabla pivot
                    $activity->products()->create([
                        'product_id' => $item['product_id'],
                        'quantity' => $item['quantity'],
                        'historical_unit_cost' => $historicalUnitCost,
                        'total_cost' => $acumulatedCost
                    ]);
                }
            }

            // 4. PROCESAR MAQUINARIA
            if ($request->has('machinery')) {
                foreach ($request->machinery as $item) {
                    $machine = Machinery::find($item['machinery_id']);

                    // Obtenemos el costo actual (Calculado en el módulo anterior)
                    // Si es Tractor usa horas, si es Camioneta usa Km (el valor hours_or_km lo refleja)
                    $hourlyCost = $machine->calculated_hourly_cost;
                    $totalCost = $hourlyCost * $item['hours_or_km'];

                    $activity->machinery()->create([
                        'machinery_id' => $item['machinery_id'],
                        'hours_or_km' => $item['hours_or_km'],
                        'historical_hourly_cost' => $hourlyCost, // Foto del costo hoy
                        'total_cost' => $totalCost
                    ]);
                }
            }

            if ($request->week_activity_id) {
                DB::table('weekly_activities')
                    ->where('id', $request->week_activity_id)
                    ->update(['status' => 'completed']);
            }

            DB::commit();

            return response()->json([
                'message' => 'Actividad registrada correctamente',
                'data' => $activity->load(['products', 'machinery'])
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['error' => $e->getMessage()], 400);
        }
    }

    /**
     * Actualizar actividad (Usado principalmente para guardar los Datos Técnicos post-registro)
     */
    public function update(Request $request, $id)
    {
        $activity = Activity::findOrFail($id);

        // Solo permitimos actualizar 'extra_data' y 'observation' por seguridad
        // para no romper los costos ya calculados.
        $data = $request->validate([
            'extra_data' => 'nullable|array',
            'observation' => 'nullable|string'
        ]);

        $activity->update($data);

        return response()->json([
            'message' => 'Datos técnicos actualizados',
            'data' => $activity
        ]);
    }

    /**
     * Ver detalle de una actividad
     */
    public function show($id)
    {
        return response()->json(
            Activity::with(['field', 'products.product', 'machinery.machine'])->findOrFail($id)
        );
    }

    /**
     * Eliminar (Reversar stock es complejo, por ahora solo borramos registro)
     * Nota: En sistemas avanzados, borrar una actividad debería devolver el stock a los lotes.
     */
    public function destroy($id)
    {
        $activity = Activity::findOrFail($id);
        $activity->delete(); // Esto borra en cascada los detalles gracias a la migración
        return response()->json(['message' => 'Actividad eliminada']);
    }

    /**
     * OBJETIVO: Traer las tareas de 'weekly_activities' que:
     * 1. Sean del usuario actual.
     * 2. Tengan estatus 'approved' o 'pending'.
     * 3. NO estén ya registradas en 'p_activities' (para no duplicar).
     */
    public function getPendingPlannedActivities(Request $request)
    {
        $userId = auth()->id();

        // 1. Definir el rango de la semana actual (Lunes a Domingo)
        $startOfWeek = Carbon::now()->startOfWeek();
        $endOfWeek   = Carbon::now()->endOfWeek();

        // 2. IDs de actividades ya ejecutadas para no repetirlas
        $executedIds = Activity::whereNotNull('week_activity_id')
            ->pluck('week_activity_id')
            ->toArray();

        // 3. Consulta filtrada por FECHA
        $pending = DB::table('weekly_activities')
            ->where('user_id', $userId)
            ->whereIn('status', ['approved', 'pending', 'in progress'])
            ->whereNotIn('id', $executedIds)

            // === FILTRO CLAVE AQUÍ ===
            ->whereBetween('date', [$startOfWeek->toDateString(), $endOfWeek->toDateString()])
            // =========================

            ->select(
                'id',
                'description as task_name',
                'work_location',
                'date',
                'status'
            )
            ->orderBy('date', 'asc')
            ->get();

        return response()->json($pending);
    }
}
