<?php

namespace Modules\Auth\Services;

use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class AuthService
{
    /**
     * Intenta autenticar al usuario y retorna el token.
     *
     * @param array $credentials
     * @return string
     * @throws ValidationException
     */
    public function authenticate(array $credentials): string
    {
        $token = Auth::guard('api')->attempt($credentials);

        if (!$token) {
            throw ValidationException::withMessages([
                'email' => ['Invalid credentials!'],
            ]);
        }

        return $token;
    }
}
