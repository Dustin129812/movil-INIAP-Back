<?php

namespace Modules\Produccion\Http\Requests\Lote;

use Illuminate\Foundation\Http\FormRequest;

class SegmentarLoteRequest extends FormRequest
{
    public function authorize() { return true; }

    public function rules()
    {
        return [
            'nombre' => 'required|string|max:100',
            'superficie_hectareas' => 'required|numeric|min:0.0001',
            'poligono_geojson' => 'required|string',
        ];
    }
}
