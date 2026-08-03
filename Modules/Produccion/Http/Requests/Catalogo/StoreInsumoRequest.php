<?php

namespace Modules\Produccion\Http\Requests\Catalogo;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Modules\Produccion\Entities\UnidadMedida;

class StoreInsumoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'unidad_medida_id' => [
                'required',
                Rule::exists(UnidadMedida::class, 'id')
            ],
            'tipo'        => 'required|in:FERTILIZANTE,SEMILLA,QUIMICO,HERRAMIENTA',
            'nombre'      => 'required|string|max:150',
            'descripcion' => 'nullable|string'
        ];
    }
}
