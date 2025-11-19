<?php

namespace Modules\Inventario\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreToolRequest extends FormRequest
{
    public function authorize() { return true; }

    public function rules()
    {
        return [
            'name' => 'required|string|max:255',
            'type' => 'required|in:TOOL',

            'acquisition_cost' => 'required|numeric|min:0',
            'acquisition_year' => 'required|integer|min:1900|max:' . (date('Y') + 1),
            'useful_life_years' => 'required|integer|min:1',

            'cost_parameters' => 'nullable|array',
            'cost_parameters.hours_per_day' => 'nullable|numeric|min:1|max:24',
            'cost_parameters.days_per_month' => 'nullable|numeric|min:1|max:31',
        ];
    }

    public function messages()
    {
        return [
            'useful_life_years.required' => 'La vida útil es necesaria para calcular la depreciación anual.',
            'cost_parameters.hours_per_day.numeric' => 'Las horas laborales deben ser un número.',
        ];
    }
}
