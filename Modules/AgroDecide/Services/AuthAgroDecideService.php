<?php

namespace Modules\AgroDecide\Services;

use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Modules\AgroDecide\Transformers\UserAgroDecideResource;
use Tymon\JWTAuth\Facades\JWTAuth;

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

        return [
            'token' => $token,
            'tecnico' => new UserAgroDecideResource($user),
        ];
    }

    public function getTokenForCookie(): string
    {
        return JWTAuth::parseToken()->fromUser(JWTAuth::user());
    }
}
