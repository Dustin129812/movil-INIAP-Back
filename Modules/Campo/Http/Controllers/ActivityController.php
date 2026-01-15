<?php

namespace Modules\Campo\Http\Controllers;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Modules\Campo\Entities\Activity;
use Modules\Inventario\Entities\Batch;
use Modules\Inventario\Entities\Machinery;
use Modules\Inventario\Entities\Product;

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
            'field_id' => 'nullable|exists:p_fields,id',
            'activity_date' => 'required|date',
            'task_type' => 'required|string', // Ej: "Fumigación"
            'extra_data' => 'nullable|array',
            'harvests' => 'nullable|array',

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

        if (!$request->field_id && !$request->prod_batch_id) {
            return response()->json([
                'error' => 'La actividad debe estar vinculada a un Terreno (Mantenimiento) o a un Cultivo (Producción).'
            ], 422);
        }

        DB::beginTransaction();

        try {
            // 2. Crear la Cabecera (La Actividad)
            $activity = Activity::create([
                'week_activity_id' => $request->week_activity_id,
                'field_id' => $request->field_id,       // Ahora aceptará NULL
                'prod_batch_id' => $request->prod_batch_id,
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

            if ($request->has('harvests') && count($request->harvests) > 0) {

                // Validación estricta SOLO si hay cosecha
                if (!$activity->prod_batch_id) {
                    throw new \Exception("Para registrar una cosecha, debes seleccionar un Lote de Producción.");
                }

                foreach ($request->harvests as $item) {
                    // Doble check para no guardar vacíos
                    if(!empty($item['quantity']) && $item['quantity'] > 0) {
                        DB::table('p_harvests')->insert([
                            'activity_id' => $activity->id,
                            'prod_batch_id' => $activity->prod_batch_id,
                            'quantity' => $item['quantity'],
                            'unit' => $item['unit'] ?? 'kg',
                            'quality_grade' => $item['quality'] ?? 'standard',
                            'created_at' => now(),
                            'updated_at' => now()
                        ]);
                    }
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
     * ACTUALIZAR ACTIVIDAD (Edición completa con reverso de stock)
     */
    /**
     * ACTUALIZAR ACTIVIDAD
     * Maneja: Edición de datos, Recálculo de costos y CANCELACIÓN (Reverso de todo).
     */
    public function update(Request $request, $id)
    {
        // 1. Buscamos la actividad con sus relaciones para poder devolver stock
        $activity = Activity::with(['products', 'machinery'])->findOrFail($id);

        DB::beginTransaction();

        try {
            // =========================================================================
            // CASO 1: CANCELACIÓN / ANULACIÓN
            // =========================================================================
            // Verificamos el status ANTES de validar los campos requeridos (fechas, etc.)
            if ($request->status === 'cancelled') {

                // Evitar doble cancelación
                if ($activity->status === 'cancelled') {
                    DB::rollBack();
                    return response()->json(['message' => 'La actividad ya estaba cancelada.'], 200);
                }

                // A. Devolvemos todo al inventario (Insumos)
                $this->restoreStock($activity);

                // B. Marcamos la ejecución como cancelada
                $activity->update(['status' => 'cancelled']);

                // C. REVERTIMOS LA PLANIFICACIÓN (Aquí estaba el problema)
                // Si esta actividad venía de una planificación semanal, la regresamos a 'pending'
                // para que vuelva a aparecer como tarjeta naranja en el Dashboard.
                if ($activity->week_activity_id) {
                    DB::table('weekly_activities')
                        ->where('id', $activity->week_activity_id)
                        ->update([
                            'status' => 'approved',   // Vuelve a estar pendiente
                            'percentage' => 0        // Reiniciamos el progreso
                        ]);
                }

                DB::commit();
                return response()->json(['message' => 'Actividad anulada. Insumos devueltos y tarea marcada como pendiente.']);
            }

            // =========================================================================
            // CASO 2: EDICIÓN DE DATOS (Si no es cancelación)
            // =========================================================================

            // Ahora sí validamos que los datos sean coherentes
            $request->validate([
                'activity_date' => 'required|date',
                'task_type' => 'required|string',
                'products' => 'nullable|array',
                'machinery' => 'nullable|array',
            ]);

            // 1. Actualizamos datos básicos de la cabecera
            $activity->update([
                'activity_date' => $request->activity_date,
                'task_type' => $request->task_type,
                'observation' => $request->observation,
                'workers_count' => $request->workers_count ?? 0,
                'labor_cost_total' => $request->labor_cost_total ?? 0,
                'extra_data' => $request->extra_data,
                // Nota: No tocamos el status aquí, sigue siendo 'completed'
            ]);

            // 2. GESTIÓN DE PRODUCTOS (Reemplazo total)
            if ($request->has('products')) {
                // a) Devolver stock de lo que se usó antes
                $this->restoreStock($activity);

                $activity->products()->delete();

                foreach ($request->products as $item) {
                    $product = Product::withoutGlobalScopes()->find($item['product_id']);

                    if (!$product) {
                        throw new \Exception(
                            "DEBUG: Falló la búsqueda del Producto ID {$item['product_id']}. " .
                            "Verifica tabla 'inv_products'. IDs existentes: " .
                            implode(',', Product::pluck('id')->toArray())
                        );
                    }

                    $qtyNeeded = $item['quantity'] ?? ($item['pivot']['quantity'] ?? 0);

                    if ($qtyNeeded > 0) {
                        if ($product->total_stock < $qtyNeeded) {
                            throw new \Exception("Stock insuficiente para {$product->name}. Stock actual: {$product->total_stock}");
                        }

                        $batches = $product->batches()
                            ->where('current_quantity', '>', 0)
                            ->where('is_active', true)
                            ->orderBy('expiration_date', 'asc')
                            ->get();

                        $acumulatedCost = 0;
                        $remainingQty = $qtyNeeded;

                        foreach ($batches as $batch) {
                            if ($remainingQty <= 0) break;
                            $take = min($batch->current_quantity, $remainingQty);

                            $batch->current_quantity -= $take;
                            $batch->save();

                            $acumulatedCost += ($take * $batch->unit_cost);
                            $remainingQty -= $take;
                        }

                        if ($remainingQty > 0) {
                            throw new \Exception("No hay lotes suficientes para cubrir la cantidad de {$product->name}. Faltan: $remainingQty");
                        }

                        $historicalUnitCost = $acumulatedCost / $qtyNeeded;
                        $activity->products()->create([
                            'product_id' => $item['product_id'],
                            'quantity' => $qtyNeeded,
                            'historical_unit_cost' => $historicalUnitCost,
                            'total_cost' => $acumulatedCost
                        ]);
                    }
                }
            }

            if ($request->has('machinery')) {
                $activity->machinery()->delete();

                foreach ($request->machinery as $item) {
                    $machine = Machinery::find($item['machinery_id']);
                    $usage = $item['hours_or_km'] ?? ($item['pivot']['hours_or_km'] ?? 0);

                    if ($usage > 0) {
                        $hourlyCost = $machine->calculated_hourly_cost;
                        $totalCost = $hourlyCost * $usage;

                        $activity->machinery()->create([
                            'machinery_id' => $item['machinery_id'],
                            'hours_or_km' => $usage,
                            'historical_hourly_cost' => $hourlyCost,
                            'total_cost' => $totalCost
                        ]);
                    }
                }
            }

            // 4. GESTIÓN DE COSECHA
            if ($request->has('harvests')) {
                DB::table('p_harvests')->where('activity_id', $activity->id)->delete();

                foreach ($request->harvests as $item) {
                    if(!empty($item['quantity']) && $item['quantity'] > 0) {
                        DB::table('p_harvests')->insert([
                            'activity_id' => $activity->id,
                            'prod_batch_id' => $activity->prod_batch_id,
                            'quantity' => $item['quantity'],
                            'unit' => $item['unit'] ?? 'kg',
                            'quality_grade' => $item['quality'] ?? 'standard',
                            'created_at' => now(),
                            'updated_at' => now()
                        ]);
                    }
                }
            }

            DB::commit();

            return response()->json([
                'message' => 'Actividad actualizada correctamente',
                'data' => $activity->fresh(['products', 'machinery'])
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['error' => 'Error: ' . $e->getMessage()], 400);
        }
    }

    /**
     * HELPER: Devuelve el stock a los lotes originales.
     * Si no guardamos batch_id en el pivote, devolvemos al lote activo con fecha de caducidad más lejana para rotación).
     */
    private function restoreStock(Activity $activity)
    {
        foreach ($activity->products as $pivot) {
            $product = Product::find($pivot->product_id);
            $qtyToReturn = $pivot->quantity;

            $targetBatch = Batch::where('product_id', $product->id)
                ->where('is_active', true)
                ->orderBy('expiration_date', 'desc')
                ->first();

            if ($targetBatch) {
                $targetBatch->current_quantity += $qtyToReturn;
                $targetBatch->save();
            } else {
                // Caso extremo: No hay lotes activos. Habría que reactivar uno o crear alerta.
                // Por ahora, asumimos que siempre hay un lote.
            }
        }
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

        $startOfWeek = Carbon::now()->startOfWeek();
        $endOfWeek   = Carbon::now()->endOfWeek();

        $executedIds = Activity::whereNotNull('week_activity_id')
            ->where('status', '!=', 'cancelled')
            ->pluck('week_activity_id')
            ->toArray();
        // ===========================

        $pending = DB::table('weekly_activities')
            ->where('user_id', $userId)
            ->whereIn('status', ['approved', 'pending', 'in progress'])

            ->whereNotIn('id', $executedIds)

            ->whereBetween('date', [$startOfWeek->toDateString(), $endOfWeek->toDateString()])
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
