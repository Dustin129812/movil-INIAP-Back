<?php

namespace Modules\Administracion\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreInventoryItemRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'type' => [
                'required',
                'string',
                Rule::in(['insumo', 'vehiculo', 'equipo'])
            ],
            'name' => ['required', 'string', 'max:200'],
            'sku'  => [
                'nullable',
                'string',
                'max:100',
                Rule::unique('administracion.inventory_items', 'sku')
            ],

            'attributes' => ['required', 'array'],

            // ---------------------------------------------------------
            // VALIDACIONES CONDICIONALES PARA EL JSON (attributes)
            // ---------------------------------------------------------

            // 🚗 Si es VEHÍCULO
            'attributes.placa' => ['required_if:type,vehiculo', 'string', 'max:20'],
            'attributes.marca' => ['required_if:type,vehiculo', 'string', 'max:100'],
            'attributes.modelo' => ['nullable', 'string', 'max:100'],
            'attributes.kilometraje_inicial' => ['nullable', 'numeric', 'min:0'],

            // 🧪 Si es INSUMO (Químicos, Fertilizantes, etc.)
            'attributes.nombre_comercial' => ['required_if:type,insumo', 'string', 'max:150'],
            'attributes.agente_activo' => ['required_if:type,insumo', 'string', 'max:150'],
            'attributes.unidad_medida' => [
                'required_if:type,insumo',
                'string',
                Rule::in(['L', 'kg', 'g', 'ml', 'unidad', 'saco', 'galon'])
            ],
            'attributes.fecha_expiracion' => ['nullable', 'date', 'after:today'],

            // 💻 Si es EQUIPO (Laptops, Proyectores, GPS)
            'attributes.tipo_equipo' => ['required_if:type,equipo', 'string', 'max:100'],
            'attributes.marca' => ['required_if:type,equipo', 'string', 'max:100'],
            'attributes.numero_serie' => ['required_if:type,equipo', 'string', 'max:100'],
            'attributes.estado_fisico' => ['nullable', 'string', 'max:200'],
        ];
    }

    /**
     * Mensajes personalizados para guiar al usuario en el Frontend.
     */
    public function messages(): array
    {
        return [
            'attributes.placa.required_if' => 'La placa es obligatoria cuando registras un vehículo.',
            'attributes.agente_activo.required_if' => 'Debes especificar el agente activo para los insumos agrícolas.',
            'attributes.numero_serie.required_if' => 'El número de serie es vital para el control de los equipos tecnológicos.',
            'attributes.unidad_medida.in' => 'La unidad de medida seleccionada no es válida en el sistema.',
        ];
    }
}
