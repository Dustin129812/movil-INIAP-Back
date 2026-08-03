<?php

namespace Modules\Produccion\Http\Requests\LibroCampo;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Modules\Produccion\Entities\LibroCampo;

class RegistrarPersonalRequest extends FormRequest
{
    public function authorize()
    {
        // Capturamos el ID sin importar si el frontend lo envía como libro_id o libro_campo_id
        $id = $this->input('libro_id') ?? $this->input('libro_campo_id');

        if (!$id) return false;

        $libro = LibroCampo::find($id);
        return $libro && $libro->estado === 'ABIERTO';
    }

    protected function prepareForValidation()
    {
        // Unificamos la variable antes de que pasen las reglas
        if ($this->has('libro_campo_id') && !$this->has('libro_id')) {
            $this->merge(['libro_id' => $this->libro_campo_id]);
        }
    }

    public function rules()
    {
        return [
            'libro_id'         => ['required', Rule::exists(LibroCampo::class, 'id')],
            'user_id'          => 'required|integer',
            'fecha'            => 'required|date',
            'labor'            => 'required|string|max:200',
            'horas_trabajadas' => 'required|numeric|min:0.1',
            'costo_hora'       => 'required|numeric|min:0.01'
        ];
    }

    public function messages()
    {
        return [
            'horas_trabajadas.min' => 'El tiempo de trabajo debe ser de al menos 0.1 horas.',
            'costo_hora.min' => 'El costo por hora debe ser mayor a 0.'
        ];
    }
}
