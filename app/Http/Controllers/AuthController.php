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
        // Intentamos autenticar al usuario
        $credentials = $request->only('email', 'password');
        if (!$token = JWTAuth::attempt($credentials)) {
            return response()->json([
                'message' => 'Invalid credentials!',
            ], 401);
        }

        // Obtenemos el usuario autenticado
        $user = Auth::user();

        // Obtenemos los roles del usuario
        $roles = $user->getRoleNames(); // Devuelve una colección de roles, pero puedes convertirlo en un array si prefieres

        // Retornar el token, rol y usuario
        return (new UserResource($user))->additional([
            'authToken' => $token,
            'roles' => $roles,  // Añadimos los roles en la respuesta
            'msg' => [
                'summary' => 'Login success',
                'detail' => 'Authentication successful',
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

    public function getUserRoles()
    {
        $user = Auth::user();
        if (!$user) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        return response()->json([
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'roles' => $user->getRoleNames(),
            ]
        ], 200);
    }
}
