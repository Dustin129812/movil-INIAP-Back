<?php

namespace Modules\Auth\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class RegisterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $rules = [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'unique:users,email'],
        ];

        // invitado, password es opcional (se genera temporal)
        if (!$this->boolean('esInvitado')) {
            $rules['password'] = ['required', 'string', 'min:6'];
        }

        return $rules;
    }
}
