<?php

namespace Modules\Produccion\Http\Requests\Catalogo;

use Illuminate\Foundation\Http\FormRequest;

class StoreMaquinariaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'nombre'     => 'required|string|max:100',
            'costo_hora' => 'required|numeric|min:0'
        ];
    }
}
