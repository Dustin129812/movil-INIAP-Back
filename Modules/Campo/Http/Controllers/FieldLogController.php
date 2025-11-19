<?php

namespace Modules\Campo\Http\Controllers;

use Illuminate\Routing\Controller;
use Illuminate\Http\Request;
use Modules\Campo\Services\FieldLogService;

class FieldLogController extends Controller
{
    protected $fieldLogService;

    public function __construct(FieldLogService $fieldLogService)
    {
        $this->fieldLogService = $fieldLogService;
    }

    /**
     * Guarda un nuevo reporte de campo y calcula costos.
     */
    public function store(Request $request)
    {
        // 1. Validamos que nos envíen lo necesario
        $validated = $request->validate([
            'week_activity_id' => 'required|exists:weekly_activities,id',
            'execution_date' => 'required|date',
            'duration_hours' => 'required|numeric|min:0.1',
            'location_name' => 'nullable|string',
            'observations' => 'nullable|string',

            // Arrays opcionales
            'machinery' => 'nullable|array',
            'machinery.*.id' => 'required_with:machinery|exists:inv_machinery,id',
            'machinery.*.hours' => 'nullable|numeric', // Opcional, si no envía usa duration_hours

            'inputs' => 'nullable|array',
            'inputs.*.batch_id' => 'required_with:inputs|exists:inv_batches,id',
            'inputs.*.quantity' => 'required_with:inputs|numeric|min:0.01',
        ]);

        try {
            // 2. Llamamos al servicio "cerebro"
            $log = $this->fieldLogService->registerExecution($validated);

            return response()->json([
                'message' => 'Actividad registrada y costos calculados exitosamente.',
                'data' => $log
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Error al procesar el registro de campo',
                'detail' => $e->getMessage()
            ], 500);
        }
    }
}
