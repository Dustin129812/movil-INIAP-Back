<?php

namespace Modules\AgroDecide\Services;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthAgroDecideService
{
    public function authenticateMobile(array $credentials): array
    {
        $token = Auth::guard('api')->attempt($credentials);

        if (!$token) {
            throw ValidationException::withMessages([
                'email' => ['Credenciales incorrectas o acceso denegado a AgroDecide.'],
            ]);
        }

        $user = auth('api')->user();

        $offlineHash = Hash::make($credentials['password']);

        return [
            'token' => $token,
            'offline_access' => [
                'user_id' => $user->id,
                'secret' => $offlineHash,
            ],
            'tecnico' => [
                'id' => $user->id,
                'nombre' => $user->name,
                'email' => $user->email,
            ]
        ];
    }
}
