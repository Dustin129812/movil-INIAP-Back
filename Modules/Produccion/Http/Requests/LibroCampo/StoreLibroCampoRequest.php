<?php

namespace Modules\Produccion\Http\Requests\LibroCampo;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Modules\Produccion\Entities\Lote;

class StoreLibroCampoRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'lote_id'      => ['required', Rule::exists(Lote::class, 'id')],
            'nombre'       => 'required|string|max:150',
            'fecha_inicio' => 'required|date',
        ];
    }
}
