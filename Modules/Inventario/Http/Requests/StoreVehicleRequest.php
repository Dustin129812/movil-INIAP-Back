<?php

namespace Modules\Inventario\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreVehicleRequest extends FormRequest
{
    public function authorize() { return true; }

    public function rules()
    {
        return [
            'name' => 'required|string|max:255',
            'type' => 'required|in:VEHICLE',

            'acquisition_cost' => 'required|numeric|min:0',
            'acquisition_year' => 'required|integer',
            'useful_life_years' => 'required|integer|min:1',

            // Para vehículos, las horas anuales SÍ son manuales (ej: 1000 horas o 20000 km convertidos)
            'annual_usage_hours' => 'required|numeric|min:1',

            // === LA PARTE COMPLEJA (JSON OBLIGATORIO) ===
            'cost_parameters' => 'required|array',

            // 1. Costos Fijos Extra
            'cost_parameters.annual_insurance' => 'required|numeric|min:0',
            'cost_parameters.annual_taxes' => 'required|numeric|min:0',

            // 2. Combustible
            'cost_parameters.fuel_consumption_per_hour' => 'required|numeric|min:0',
            'cost_parameters.fuel_price_per_gallon' => 'required|numeric|min:0',

            // 3. Mantenimiento (Aceite, Llantas, Revisiones)
            // Aceite
            'cost_parameters.oil_change_cost' => 'required|numeric|min:0',
            'cost_parameters.oil_change_interval_hours' => 'required|numeric|min:1',

            // Llantas
            'cost_parameters.tire_set_cost' => 'required|numeric|min:0',
            'cost_parameters.tire_duration_hours' => 'required|numeric|min:1',

            // Mantenimiento General
            'cost_parameters.maintenance_cost' => 'required|numeric|min:0',
            'cost_parameters.maintenance_interval_hours' => 'required|numeric|min:1',
        ];
    }

    public function attributes()
    {
        return [
            'cost_parameters.fuel_consumption_per_hour' => 'consumo de combustible',
            'cost_parameters.tire_set_cost' => 'costo del juego de llantas',
        ];
    }
}
