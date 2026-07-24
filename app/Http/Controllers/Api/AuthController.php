<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use App\Models\Dispositivo;
use Carbon\Carbon;

class AuthController extends Controller
{
    /**
     * Registro de usuario
     */
    public function register(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:6',
            'uuid' => 'required|string',
            'modelo' => 'nullable|string',
            'sistema_operativo' => 'nullable|string'
        ]);

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
                'ultimo_login' => Carbon::now()
            ]
        );

        $token = Auth::guard('api')->login($user);

        return response()->json([
            'success' => true,
            'message' => 'Registro exitoso',
            'ID' => $user->id,
            'NOMBRE' => $user->name,
            'CORREO' => $user->email,
            'TOKEN' => $token
        ], 201);
    }

    /**
     * Login
     */
    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required|string',
            'uuid' => 'required|string',
            'modelo' => 'nullable|string',
            'sistema_operativo' => 'nullable|string'
        ]);

        $credentials = $request->only('email', 'password');

        if (!$token = Auth::guard('api')->attempt($credentials)) {
            return response()->json([
                'success' => false,
                'message' => 'Credenciales incorrectas'
            ], 401);
        }

        $user = Auth::guard('api')->user();

        Dispositivo::updateOrCreate(
            ['uuid' => $request->uuid],
            [
                'user_id' => $user->id,
                'modelo' => $request->modelo,
                'sistema_operativo' => $request->sistema_operativo,
                'ultimo_login' => Carbon::now()
            ]
        );

        return response()->json([
            'success' => true,
            'message' => 'Login exitoso',
            'ID' => $user->id,
            'NOMBRE' => $user->name,
            'CORREO' => $user->email,
            'TOKEN' => $token
        ]);
    }

    /**
     * Usuario autenticado
     */
    public function me()
    {
        $user = Auth::guard('api')->user();
        return response()->json([
            'success' => true,
            'ID' => $user->id,
            'NOMBRE' => $user->name,
            'CORREO' => $user->email
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
    public function refresh(Request $request)
{
    $user = $request->user();
    
    // 1. Eliminar el token actual que se está usando
    $user->currentAccessToken()->delete();
    
    // 2. Crear un nuevo token
    $newToken = $user->createToken('auth_token')->plainTextToken;

    return response()->json([
        'success' => true,
        'message' => 'Token actualizado correctamente',
        'access_token' => $newToken,
        'token_type' => 'Bearer'
    ]);
}
}
