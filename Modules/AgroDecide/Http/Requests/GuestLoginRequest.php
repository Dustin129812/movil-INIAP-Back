<?php

namespace Modules\AgroDecide\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class GuestLoginRequest extends FormRequest
{
    /**
     * Determina si el usuario está autorizado a realizar esta petición.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Reglas de validación aplicadas a la petición.
     */
    public function rules(): array
    {
        return [
            'device_uuid' => ['required', 'string', 'uuid'],
            'modelo'      => ['nullable', 'string', 'max:255'],
            'sistema_operativo' => ['nullable', 'string', 'max:255'],
            'hardware'    => ['nullable', 'string', 'max:255'],
        ];
    }
}
