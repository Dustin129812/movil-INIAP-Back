<?php

namespace Modules\AgroDecide\Services;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Modules\AgroDecide\Entities\UserAgroDecide;
use Tymon\JWTAuth\Facades\JWTAuth;

class AuthUserAgroDecideService
{
    public function authenticate(array $credentials): array
    {
        $user = UserAgroDecide::where('correo_institucional', $credentials['correo_institucional'])->first();

        if (!$user) {
            throw ValidationException::withMessages([
                'correo_institucional' => ['Usuario no encontrado.'],
            ]);
        }

        if ($user->estado !== 'activo') {
            throw ValidationException::withMessages([
                'correo_institucional' => ['Usuario inactivo.'],
            ]);
        }

        if (!Hash::check($credentials['password'], $user->password)) {
            throw ValidationException::withMessages([
                'password' => ['Contraseña incorrecta.'],
            ]);
        }

        $payload = [
            'sub' => $user->id,
            'role' => 'user',
            //'correo' => $user->correo_institucional,
            //'nombre' => $user->nombre,
            'iat' => time(),
            'exp' => time() + (43200 * 60), // 30 días
        ];

        $token = JWTAuth::getJWTProvider()->encode($payload);

        return [
            'token' => $token,
            'user' => [
                'id' => $user->id,
                'correo_institucional' => $user->correo_institucional,
                'nombre' => $user->nombre,
            ],
        ];
    }
}
