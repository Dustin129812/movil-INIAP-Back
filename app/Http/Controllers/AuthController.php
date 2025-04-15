<?php

namespace App\Http\Controllers;

use App\Http\Resources\UserResource;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Tymon\JWTAuth\Facades\JWTAuth;
use Tymon\JWTAuth\Exceptions\JWTException;

class AuthController extends Controller
{
    public function login(Request $request)
    {
        // Validación de las credenciales
        $credentials = $request->only('email', 'password');

        // Intentar autenticar al usuario
        if (!$token = JWTAuth::attempt($credentials)) {
            return response()->json([
                'message' => 'Invalid credentials!',
            ], 401);
        }

        // Obtener el usuario autenticado
        $user = Auth::user();

        // Retornar la respuesta con los datos del usuario y el token JWT
        return (new UserResource($user))->additional([
            'authToken' => $token,
            'msg' => [
                'summary' => 'Login success',
                'detail' => $token,
                'code' => '200',
            ]
        ])->response()->setStatusCode(200);
    }

    public function logout()
    {
        try {
            JWTAuth::invalidate(JWTAuth::getToken());
            return response()->json(['message' => 'Successfully logged out'], 200);
        } catch (JWTException $e) {
            return response()->json(['message' => 'Failed to log out, please try again'], 500);
        }
    }
}
