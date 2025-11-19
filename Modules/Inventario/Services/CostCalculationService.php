<?php

namespace Modules\Inventario\Services;

use Modules\Inventario\Entities\Machinery;

class CostCalculationService
{
    /**
     * Punto de entrada principal: Recibe una maquinaria y actualiza su costo hora.
     */
    public function updateHourlyCost(Machinery $machine): void
    {
        $cost = 0.0;

        if ($machine->type === 'TOOL') {
            $cost = $this->calculateToolCost($machine);
        } elseif ($machine->type === 'VEHICLE') {
            $cost = $this->calculateVehicleCost($machine);
        }

        // Guardamos el costo calculado (redondeado a 4 decimales para precisión)
        $machine->calculated_hourly_cost = round($cost, 4);

        // saveQuietly evita disparar eventos recursivos si tienes observers
        $machine->saveQuietly();
    }

    /**
     * Lógica EXACTA de tu Excel "Depreciación herramientas GET"
     * Fórmula: ((Valor / VidaUtil) / 12) / (HorasDia * DiasMes)
     */
    private function calculateToolCost(Machinery $tool): float
    {
        if ($tool->acquisition_cost <= 0 || $tool->useful_life_years <= 0) {
            return 0;
        }

        // 1. Obtener parámetros de tiempo (Si no existen, usamos tus defaults: 8h/20d)
        $params = $tool->cost_parameters ?? [];
        $hoursPerDay = $params['hours_per_day'] ?? 8;
        $daysPerMonth = $params['days_per_month'] ?? 20;

        // 2. Depreciación Anual (=+C5/10)
        $annualDepreciation = $tool->acquisition_cost / $tool->useful_life_years;

        // 3. Depreciación Mensual (=+E5/12)
        $monthlyDepreciation = $annualDepreciation / 12;

        // 4. Depreciación Hora (=F5/(8*20))
        $monthlyHours = $hoursPerDay * $daysPerMonth; // 160 horas estándar

        if ($monthlyHours <= 0) return 0;

        return $monthlyDepreciation / $monthlyHours;
    }

    /**
     * Lógica EXACTA de tu Excel "Costo hora uso vehiculo"
     * Costos Fijos + Costos Variables (Combustible, Aceite, Llantas, Mtto)
     */
    private function calculateVehicleCost(Machinery $vehicle): float
    {
        $p = $vehicle->cost_parameters; // JSON con los datos
        $annualHours = $vehicle->annual_usage_hours ?: 1000; // Evitar div/0

        // --- A. COSTOS FIJOS (Por Hora) ---
        // 1. Amortización Anual (Valor / Vida)
        $amortization = $vehicle->acquisition_cost / ($vehicle->useful_life_years ?: 1);

        // 2. Otros fijos (Seguro + Impuestos)
        $otherFixed = ($p['annual_insurance'] ?? 0) + ($p['annual_taxes'] ?? 0);

        // Total Fijo por Hora = (Amortización + Otros) / Horas Anuales
        $fixedHourly = ($amortization + $otherFixed) / $annualHours;


        // --- B. COSTOS VARIABLES (Por Hora de operación) ---

        // 1. Combustible (Galones/Hora * Precio)
        $fuel = ($p['fuel_consumption_per_hour'] ?? 0) * ($p['fuel_price_per_gallon'] ?? 0);

        // 2. Aceite (Costo Cambio / Intervalo Horas)
        $oil = 0;
        if (!empty($p['oil_change_interval_hours'])) {
            $oil = ($p['oil_change_cost'] ?? 0) / $p['oil_change_interval_hours'];
        }

        // 3. Llantas (Costo Juego / Duración Horas)
        $tires = 0;
        if (!empty($p['tire_duration_hours'])) {
            $tires = ($p['tire_set_cost'] ?? 0) / $p['tire_duration_hours'];
        }

        // 4. Mantenimiento General (Costo Revisión / Intervalo Horas)
        $maintenance = 0;
        if (!empty($p['maintenance_interval_hours'])) {
            $maintenance = ($p['maintenance_cost'] ?? 0) / $p['maintenance_interval_hours'];
        }

        // Total
        return $fixedHourly + $fuel + $oil + $tires + $maintenance;
    }
}
