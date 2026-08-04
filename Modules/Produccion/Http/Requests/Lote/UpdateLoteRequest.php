<?php

namespace Modules\Produccion\Http\Requests\Lote;

use Illuminate\Foundation\Http\FormRequest;

class UpdateLoteRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'nombre' => 'sometimes|required|string|max:100',
            'estado' => 'sometimes|required|string',
            'poligono_geojson' => 'nullable|string',
            'superficie_hectareas' => 'nullable|numeric|min:0.0001',
        ];
    }
}
