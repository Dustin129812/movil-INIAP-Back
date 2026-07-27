<?php

namespace App\Auth\Requests;

use Illuminate\Foundation\Http\FormRequest;

class LoginRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'email' => 'required|email',
            'password' => 'required|string',
            'uuid' => 'required|string',
            'modelo' => 'nullable|string',
            'sistema_operativo' => 'nullable|string'
        ];
    }
}