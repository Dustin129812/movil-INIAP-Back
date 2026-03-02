<?php

namespace Modules\Produccion\Http\Requests\LibroCampo;

use Illuminate\Foundation\Http\FormRequest;

class RegistrarClimaRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'libro_id' => 'required|exists:produccion.libros_campo,id',
            'fecha_registro' => 'required|date',
            'temperatura' => 'required|numeric',
            'humedad' => 'required|numeric|between:0,100',
            'precipitacion' => 'nullable|numeric',
            'viento_velocidad' => 'nullable|string',
            'nubosidad' => 'nullable|string',
            'notas_clima' => 'nullable|string',
        ];
    }

    public function authorize(): bool
    {
        return true;
    }
}
