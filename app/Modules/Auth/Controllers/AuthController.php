<?php

namespace App\Modules\Auth\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Auth\Requests\LoginRequest;
use App\Modules\Auth\Requests\RegisterRequest;
use App\Models\Dispositivo;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Carbon\Carbon;

class AuthController extends Controller
{
    public function register(RegisterRequest $request): JsonResponse
    {
        $user = \App\Models\User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
        ]);

        Dispositivo::updateOrCreate(
            ['uuid' => $request->uuid],
            [
                'user_id' => $user->id,
                'modelo' => $request->modelo,
                'sistema_operativo' => $request->sistema_operativo,
                'ultimo_login' => Carbon::now(),
            ]
        );

        $token = Auth::guard('api')->login($user);

        return response()->json([
            'success' => true,
            'message' => 'Registro exitoso',
            'ID' => $user->id,
            'NOMBRE' => $user->name,
            'CORREO' => $user->email,
            'TOKEN' => $token,
        ], 201);
    }

    public function login(LoginRequest $request): JsonResponse
    {
        $credentials = $request->only('email', 'password');

        if (!$token = Auth::guard('api')->attempt($credentials)) {
            return response()->json([
                'success' => false,
                'message' => 'Credenciales incorrectas',
            ], 401);
        }

        $user = Auth::guard('api')->user();

        Dispositivo::updateOrCreate(
            ['uuid' => $request->uuid],
            [
                'user_id' => $user->id,
                'modelo' => $request->modelo,
                'sistema_operativo' => $request->sistema_operativo,
                'ultimo_login' => Carbon::now(),
            ]
        );

        return response()->json([
            'success' => true,
            'message' => 'Login exitoso',
            'ID' => $user->id,
            'NOMBRE' => $user->name,
            'CORREO' => $user->email,
            'TOKEN' => $token,
        ]);
    }

    public function me(): JsonResponse
    {
        $user = Auth::guard('api')->user();
        return response()->json([
            'success' => true,
            'ID' => $user->id,
            'NOMBRE' => $user->name,
            'CORREO' => $user->email,
        ]);
    }

    public function logout(): JsonResponse
    {
        Auth::guard('api')->logout();
        return response()->json([
            'success' => true,
            'message' => 'Sesión cerrada correctamente',
        ]);
    }

    public function refresh(): JsonResponse
    {
        $user = Auth::guard('api')->user();
        $user->currentAccessToken()->delete();
        $newToken = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'success' => true,
            'message' => 'Token actualizado correctamente',
            'access_token' => $newToken,
            'token_type' => 'Bearer',
        ]);
    }
}
