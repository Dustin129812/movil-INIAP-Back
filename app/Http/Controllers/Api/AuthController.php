<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Dispositivo;
use Carbon\Carbon;

class AuthController extends Controller
{
    /**
     * Login
     */
    public function login(Request $request)
    {
        // Validar datos recibidos
        $request->validate([
            'email' => 'required|email',
            'password' => 'required|string',
            'uuid' => 'required|string',
            'modelo' => 'nullable|string',
            'sistema_operativo' => 'nullable|string'
        ]);

        // Credenciales
        $credentials = $request->only('email', 'password');

        // Intentar iniciar sesión
        if (!$token = Auth::guard('api')->attempt($credentials)) {
            return response()->json([
                'success' => false,
                'message' => 'Credenciales incorrectas'
            ], 401);
        }

        // Usuario autenticado
        $user = Auth::guard('api')->user();

        // Registrar o actualizar dispositivo
        Dispositivo::updateOrCreate(
            [
                'uuid' => $request->uuid
            ],
            [
                'user_id' => $user->id,
                'modelo' => $request->modelo,
                'sistema_operativo' => $request->sistema_operativo,
                'ultimo_login' => Carbon::now()
            ]
        );

        // Respuesta
        return response()->json([
            'success' => true,
            'message' => 'Login exitoso',
            'access_token' => $token,
            'token_type' => 'Bearer',
            'user' => $user
        ]);
    }

    /**
     * Usuario autenticado
     */
    public function me()
    {
        return response()->json([
            'success' => true,
            'user' => Auth::guard('api')->user()
        ]);
    }

    /**
     * Cerrar sesión
     */
    public function logout()
    {
        Auth::guard('api')->logout();

        return response()->json([
            'success' => true,
            'message' => 'Sesión cerrada correctamente'
        ]);
    }

    /**
     * Refrescar token
     */
    public function refresh()
    {
        return response()->json([
            'success' => true,
            'message' => 'Token actualizado correctamente',
            'access_token' => Auth::guard('api')->refresh(),
            'token_type' => 'Bearer'
        ]);
    }
}