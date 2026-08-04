<?php

namespace Modules\Kopia\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Modules\Kopia\Entities\Cultivo;

class StoreVariedadRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'cultivo_id' => ['required', Rule::exists(Cultivo::class, 'id')],
            'nombre' => ['required', 'string', 'max:255'],
            'caracteristicas_base' => ['nullable', 'array'],
        ];
    }
}
