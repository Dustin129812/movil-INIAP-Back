<?php

namespace Modules\Produccion\Http\Requests\LibroCampo;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Modules\Produccion\Entities\LibroCampo;
use Modules\Produccion\Entities\Bodega;
use Modules\Produccion\Entities\Insumo;

class CosecharLibroRequest extends FormRequest
{
    public function authorize()
    {
        $libroId = $this->route('id');
        $libro = LibroCampo::find($libroId);

        return $libro && $libro->estado === 'ABIERTO';
    }

    public function rules()
    {
        return [
            'fecha_cosecha'       => 'required|date',
            'cantidad_cosechada'  => 'required|numeric|min:0.01',
            'insumo_cosechado_id' => ['required', Rule::exists(Insumo::class, 'id')],
            'bodega_id'           => ['required', Rule::exists(Bodega::class, 'id')],
        ];
    }

    public function messages()
    {
        return [
            'cantidad_cosechada.min' => 'La cantidad cosechada debe ser mayor a 0.',
            'insumo_cosechado_id.exists' => 'El producto/insumo final seleccionado no es válido en el catálogo.',
            'bodega_id.exists' => 'La bodega de destino no existe.'
        ];
    }
}
