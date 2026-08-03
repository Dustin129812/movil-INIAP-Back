<?php

namespace Modules\Produccion\Http\Requests\LibroCampo;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Modules\Produccion\Entities\LibroCampo;
use Modules\Produccion\Entities\Maquinaria;

class RegistrarMaquinariaRequest extends FormRequest
{
    public function authorize()
    {
        $libro = LibroCampo::find($this->libro_id);
        return $libro && $libro->estado === 'ABIERTO';
    }

    public function rules()
    {
        return [
            'libro_id'      => ['required', Rule::exists(LibroCampo::class, 'id')],
            'maquinaria_id' => ['required', Rule::exists(Maquinaria::class, 'id')],
            'fecha'         => 'required|date',
            'horas_uso'     => 'required|numeric|min:0.1',
        ];
    }

    public function messages()
    {
        return [
            'libro_id.exists' => 'El libro de campo no existe o no está disponible.'
        ];
    }
}
