<?php

namespace Modules\Produccion\Http\Requests\Catalogo;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Modules\Produccion\Entities\UnidadMedida;

class StoreUnidadRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'nombre' => [
                'required',
                'string',
                Rule::unique(UnidadMedida::class, 'nombre')
            ],
            'abreviatura' => 'required|string|max:10'
        ];
    }
}
