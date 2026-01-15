<?php

namespace Modules\Campo\Services;

use Illuminate\Support\Facades\DB;
use Modules\Campo\Entities\FieldLog;
use Modules\Inventario\Entities\Batch;
use Modules\Inventario\Entities\Machinery;
use Modules\Investigacion\Entities\WeekActivity;

class FieldLogService
{
    public function registerExecution(array $data)
    {
        return DB::transaction(function () use ($data) {
            // 1. Obtener la actividad planificada (Tu módulo original)
            // Cargamos 'logisticSupportUsers' para saber quién trabajó y cobrar su mano de obra
            $activity = WeekActivity::with('logisticSupportUsers')->findOrFail($data['week_activity_id']);

            // 2. Crear la cabecera del reporte
            $log = FieldLog::create([
                'week_activity_id' => $data['week_activity_id'],
                'execution_date' => $data['execution_date'],
                'duration_hours' => $data['duration_hours'],
                'location_name' => $data['location_name'] ?? null,
                'observations' => $data['observations'] ?? null,
                // Los costos inician en 0, los calculamos abajo
                'labor_cost' => 0,
                'machinery_cost' => 0,
                'input_cost' => 0,
                'total_cost' => 0
            ]);

            // --- A. CÁLCULO DE MANO DE OBRA (Labor) ---
            // Fórmula: (Sueldo Base / 240 horas mensuales) * Horas trabajadas
            $laborCostTotal = 0;
            foreach ($activity->logisticSupportUsers as $user) {
                // Si el usuario no tiene base_salary, usamos el básico de $460 por defecto
                $baseSalary = $user->base_salary > 0 ? $user->base_salary : 460.00;

                // Costo hora hombre (30 días * 8 horas = 240 horas)
                $costoHoraHombre = $baseSalary / 240;

                $laborCostTotal += ($costoHoraHombre * $data['duration_hours']);
            }

            // --- B. CÁLCULO DE MAQUINARIA (Depreciación) ---
            $machineryCostTotal = 0;
            if (!empty($data['machinery'])) {
                // Esperamos un array de IDs: [1, 5] o con horas específicas
                foreach ($data['machinery'] as $machineInput) {
                    $machineId = $machineInput['id'];
                    $hoursUsed = $machineInput['hours'] ?? $data['duration_hours']; // Si no especifica, usa el total de la labor

                    $machine = Machinery::findOrFail($machineId);

                    // Guardamos la relación y la "foto" del costo ($0.02) en ese momento
                    $log->machinery()->attach($machineId, [
                        'hours_used' => $hoursUsed,
                        'hourly_cost_snapshot' => $machine->hourly_depreciation_cost,
                        'total_line_cost' => $machine->hourly_depreciation_cost * $hoursUsed
                    ]);

                    $machineryCostTotal += ($machine->hourly_depreciation_cost * $hoursUsed);
                }
            }

            // --- C. CÁLCULO DE INSUMOS (Descuento de Inventario) ---
            $inputCostTotal = 0;
            if (!empty($data['inputs'])) {
                foreach ($data['inputs'] as $inputData) {
                    // inputData: ['batch_id' => 10, 'quantity' => 5]
                    $batch = Batch::findOrFail($inputData['batch_id']);

                    // Validar stock
                    if ($batch->current_quantity < $inputData['quantity']) {
                        throw new \Exception("Stock insuficiente en el lote {$batch->batch_code}...");
                    }

                    // Descontar del inventario real
                    $batch->decrement('current_quantity', $inputData['quantity']);

                    // Calcular costo (Costo del lote * Cantidad usada)
                    $costLine = $batch->unit_cost * $inputData['quantity'];

                    $log->inputs()->attach($batch->id, [
                        'quantity_used' => $inputData['quantity'],
                        'unit_cost_snapshot' => $batch->unit_cost,
                        'total_line_cost' => $costLine
                    ]);

                    $inputCostTotal += $costLine;
                }
            }

            // 3. Actualizar los totales finales en el reporte
            $log->update([
                'labor_cost' => $laborCostTotal,
                'machinery_cost' => $machineryCostTotal,
                'input_cost' => $inputCostTotal,
                'total_cost' => $laborCostTotal + $machineryCostTotal + $inputCostTotal
            ]);

            $activity->update(['status' => 'completed', 'percentage' => 100]);

            return $log;
        });
    }
}
