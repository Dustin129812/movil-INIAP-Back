<?php

namespace Modules\Produccion\Http\Controllers;

use Illuminate\Routing\Controller;
use Illuminate\Http\Request;
use Modules\Produccion\Entities\ProductionPlan;
use Illuminate\Support\Facades\DB;

class ProductionPlanController extends Controller
{
    // Listar las planificaciones (para una tabla en el front)
    public function index()
    {
        // Usamos 'with' para traer los nombres de las relaciones y no solo los IDs
        $plans = ProductionPlan::with([
            'variety.crop',       // Para ver el cultivo de la variedad
            'lot',                // Para ver el nombre del lote
            'seedCategory'        // Para ver la categoría de la semilla
        ])->get();

        return response()->json($plans);
    }

    // Guardar una nueva planificación "Donde se junta todo"
    public function store(Request $request)
    {
        $request->validate([
            // 1. Qué y Dónde
            'variety_id' => 'required|exists:varieties,id',
            'lot_id'     => 'required|exists:lots,id',

            // 2. Insumo (Semilla)
            'seed_quantity'    => 'required|numeric|min:0',
            'seed_category_id' => 'required|exists:categories,id', // Validar que sea categoría de semilla

            // 3. Proyección (Output)
            'expected_quantity' => 'required|numeric|min:0',
            'unit_of_measure'   => 'required|string', // kg, ton, etc.

            // 4. Admin
            'expense_type' => 'required|string',
            'observation'  => 'nullable|string'
        ]);

        try {
            DB::beginTransaction();

            $plan = ProductionPlan::create([
                'variety_id'        => $request->variety_id,
                'lot_id'            => $request->lot_id,
                'seed_quantity'     => $request->seed_quantity,
                'seed_category_id'  => $request->seed_category_id,
                'expected_quantity' => $request->expected_quantity,
                'unit_of_measure'   => $request->unit_of_measure,
                'expense_type'      => $request->expense_type,
                'observation'       => $request->observation,
            ]);

            DB::commit();

            return response()->json([
                'message' => 'Planificación creada exitosamente',
                'data' => $plan
            ], 201);

        } catch (\Exception $e) {
            DB::rollback();
            return response()->json(['error' => 'Error al guardar la planificación: ' . $e->getMessage()], 500);
        }
    }
}
