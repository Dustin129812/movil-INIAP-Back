<?php

namespace Modules\AgroDecide\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Modules\AgroDecide\Entities\Cultivo;

class StoreCultivoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            // Cambiamos el string quemado por Rule::unique()
            'nombre' => ['required', 'string', 'max:255', Rule::unique(Cultivo::class, 'nombre')],
            'nombre_cientifico' => ['nullable', 'string', 'max:255'],
        ];
    }
}
