<?php

namespace App\Auth\Services;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
use App\Models\Dispositivo;
use Carbon\Carbon;

class AuthService
{
    public function register($request)
    {
        $user = User::create([
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

    public function login($request)
    {
        $credentials = $request->only('email', 'password');

        if (!$token = Auth::guard('api')->attempt($credentials)) {

            return response()->json([
                'success' => false,
                'message' => 'Credenciales incorrectas'
            ],401);

        }

        $user = Auth::guard('api')->user();

        Dispositivo::updateOrCreate(
            ['uuid'=>$request->uuid],
            [
                'user_id'=>$user->id,
                'modelo'=>$request->modelo,
                'sistema_operativo'=>$request->sistema_operativo,
                'ultimo_login'=>Carbon::now()
            ]
        );

        return response()->json([
            'success'=>true,
            'message'=>'Login exitoso',
            'ID'=>$user->id,
            'NOMBRE'=>$user->name,
            'CORREO'=>$user->email,
            'TOKEN'=>$token
        ]);
    }

    public function me()
    {
        $user = Auth::guard('api')->user();

        return response()->json([
            'success'=>true,
            'ID'=>$user->id,
            'NOMBRE'=>$user->name,
            'CORREO'=>$user->email
        ]);
    }

    public function logout()
    {
        Auth::guard('api')->logout();

        return response()->json([
            'success'=>true,
            'message'=>'Sesión cerrada correctamente'
        ]);
    }

    public function refresh($request)
    {
        $user = $request->user();

        $user->currentAccessToken()->delete();

        $newToken = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'success'=>true,
            'message'=>'Token actualizado correctamente',
            'access_token'=>$newToken,
            'token_type'=>'Bearer'
        ]);
    }
}

class UserService
{
    /**
     * Obtiene el usuario autenticado.
     */
    public function getAuthenticatedUser()
    {
        return Auth::guard('api')->user();
    }
}