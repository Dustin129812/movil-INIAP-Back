<?php

namespace Modules\Transferencia\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ImportarDpaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Asumimos que el middleware del admin ya protege esta ruta
    }

    public function rules(): array
    {
        return [
            'archivo_dpa' => ['required', 'file', 'mimes:csv,txt', 'max:5120'], // Máximo 5MB
        ];
    }
}
