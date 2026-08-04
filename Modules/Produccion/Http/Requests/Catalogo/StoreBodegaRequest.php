<?php

namespace Modules\Produccion\Http\Requests\Catalogo;

use Illuminate\Foundation\Http\FormRequest;

class StoreBodegaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'location_id' => 'required|exists:locations,id',
            'nombre'      => 'required|string|max:100',
            'descripcion' => 'nullable|string'
        ];
    }
}
