<?php

namespace Modules\Administracion\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateInventoryItemRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $itemId = $this->route('id');

        return [
            'type' => ['required', 'string', Rule::in(['insumo', 'vehiculo', 'equipo'])],
            'name' => ['required', 'string', 'max:200'],
            'sku'  => [
                'nullable',
                'string',
                'max:100',
                Rule::unique('administracion.inventory_items', 'sku')->ignore($itemId)
            ],

            'attributes' => ['required', 'array'],
            'is_active' => ['boolean'],

            'attributes.placa' => ['required_if:type,vehiculo', 'string', 'max:20'],
            'attributes.marca' => ['required_if:type,vehiculo', 'string', 'max:100'],

            'attributes.nombre_comercial' => ['required_if:type,insumo', 'string', 'max:150'],
            'attributes.agente_activo' => ['required_if:type,insumo', 'string', 'max:150'],
            'attributes.unidad_medida' => ['required_if:type,insumo', 'string', Rule::in(['L', 'kg', 'g', 'ml', 'unidad', 'saco', 'galon'])],

            'attributes.tipo_equipo' => ['required_if:type,equipo', 'string', 'max:100'],
            'attributes.numero_serie' => ['required_if:type,equipo', 'string', 'max:100'],
        ];
    }
}
