<?php

namespace Modules\Produccion\Http\Requests\LibroCampo;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Modules\Produccion\Entities\LibroCampo;
use Modules\Produccion\Entities\Bodega;
use Modules\Produccion\Entities\Insumo;

class RegistrarLaborRequest extends FormRequest
{
    /**
     * Determina si el usuario está autorizado a hacer esta petición.
     */
    public function authorize()
    {
        // Validamos que el libro exista y esté ABIERTO
        $libro = LibroCampo::find($this->libro_id);
        return $libro && $libro->estado === 'ABIERTO';
    }

    /**
     * Reglas de validación.
     */
    public function rules()
    {
        return [
            'libro_id' => ['required', Rule::exists(LibroCampo::class, 'id')],
            'bodega_id' => ['required', Rule::exists(Bodega::class, 'id')],
            'insumo_id' => ['required', Rule::exists(Insumo::class, 'id')],
            'fecha' => 'required|date',
            'labor' => 'required|string|max:200',
            'cantidad' => 'required|numeric|min:0.0001',
            'observaciones' => 'nullable|string'
        ];
    }

    public function messages()
    {
        return [
            'libro_id.exists' => 'El libro de campo no existe.',
            'bodega_id.exists' => 'La bodega seleccionada no es válida.',
            'insumo_id.exists' => 'El insumo seleccionado no es válido.',
            'cantidad.min' => 'La cantidad debe ser mayor a 0.'
        ];
    }
}
