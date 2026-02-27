<?php

namespace Modules\Produccion\Http\Requests\Kardex;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Modules\Produccion\Entities\Bodega;
use Modules\Produccion\Entities\Insumo;

class IngresoKardexRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'bodega_id'            => ['required', 'integer', Rule::exists(Bodega::class, 'id')],
            'insumo_id'            => ['required', 'integer', Rule::exists(Insumo::class, 'id')],
            'cantidad'             => 'required|numeric|min:0.0001',
            'costo_unitario'       => 'required|numeric|min:0.0001',
            'documento_referencia' => 'nullable|string|max:150',
        ];
    }
}
