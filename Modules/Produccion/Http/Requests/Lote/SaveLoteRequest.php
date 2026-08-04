<?php

namespace Modules\Produccion\Http\Requests\Lote;

use Illuminate\Foundation\Http\FormRequest;

class SaveLoteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $rules = [
            'nombre'               => 'required|string|max:150',
            'superficie_hectareas' => 'required|numeric|min:0.1',
            'location_id'          => 'required|integer|exists:locations,id',
            'observaciones'        => 'nullable|string|max:1000',
        ];

        if ($this->isMethod('put') || $this->isMethod('patch')) {
            $loteId = $this->route('lote');
            $rules['codigo'] = "required|string|unique:produccion.lotes,codigo,{$loteId}";
        }

        return $rules;
    }

    public function messages(): array
    {
        return [
            'location_id.exists' => 'La estación o ubicación seleccionada no es válida.',
        ];
    }
}
