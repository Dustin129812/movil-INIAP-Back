<?php

namespace App\Http\Controllers;

use App\Http\Resources\UserResource;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Tymon\JWTAuth\Exceptions\JWTException;
use Tymon\JWTAuth\Facades\JWTAuth;

class AuthController extends Controller
{
    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required', // Quité la validación de min:8 por si los passwords de fiasa son diferentes
        ]);

        $credentials = $request->only('email', 'password');
        $token = null;

        if ($token = Auth::guard('api')->attempt($credentials)) {
            $user = Auth::guard('api')->user();

        } else {
            return response()->json([
                'message' => 'Invalid credentials!',
            ], 401);
        }

        $roles = $user->getRoleNames();

        return (new UserResource($user))->additional([
            '__session' => $token,
            'roles' => $roles,
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
