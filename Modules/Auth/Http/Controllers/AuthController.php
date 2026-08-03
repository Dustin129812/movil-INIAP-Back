<?php

namespace Modules\Auth\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Resources\UserResource;
use App\Models\User;
use App\Models\Dispositivo;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Modules\Auth\Http\Requests\LoginRequest;
use Modules\Auth\Http\Requests\RegisterRequest;
use Modules\Auth\Services\AuthService;
use Tymon\JWTAuth\Exceptions\JWTException;
use Tymon\JWTAuth\Facades\JWTAuth;

class AuthController extends Controller
{
    public function __construct(
        private readonly AuthService $authService
    ) {}

    public function login(LoginRequest $request): JsonResponse
    {
        $credentials = $request->only('email', 'password');
        $token = $this->authService->authenticate($credentials);
        $user = auth('api')->user();

        if ($request->uuid) {
            Dispositivo::updateOrCreate(
                ['uuid' => $request->uuid],
                [
                    'user_id' => $user->id,
                    'modelo' => $request->modelo,
                    'sistema_operativo' => $request->sistema_operativo,
                    'ultimo_login' => Carbon::now(),
                ]
            );
        }

        return response()->json([
            'success' => true,
            'TOKEN' => $token,
            'ID' => $user->id,
            'NOMBRE' => $user->name,
            'CORREO' => $user->email,
        ], 200);
    }

    public function register(RegisterRequest $request): JsonResponse
    {
        $user = new User();
        $user->name = $request->name;
        $user->email = $request->email;

        // invitado, generar contraseña temporal
        if ($request->boolean('esInvitado')) {
            $user->password = Hash::make(bin2hex(random_bytes(8)));
        } else {
            $user->password = Hash::make($request->password);
        }

        $user->save();

        if ($request->uuid) {
            Dispositivo::updateOrCreate(
                ['uuid' => $request->uuid],
                [
                    'user_id' => $user->id,
                    'modelo' => $request->modelo,
                    'sistema_operativo' => $request->sistema_operativo,
                    'ultimo_login' => Carbon::now(),
                ]
            );
        }

        $token = JWTAuth::fromUser($user);

        return response()->json([
            'success' => true,
            'TOKEN' => $token,
            'ID' => $user->id,
            'NOMBRE' => $user->name,
            'CORREO' => $user->email,
        ], 201);
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

    public function loginInvitado(Request $request): JsonResponse
    {
        $request->validate([
            'email' => 'required|email',
            'uuid' => 'required|string',
        ]);

        $user = User::firstWhere('email', $request->email);

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Usuario invitado no encontrado',
            ], 404);
        }

        // Verificación con ->query() para evitar falsas alertas de Intelephense
        $dispositivo = Dispositivo::query()
            ->where('user_id', $user->id)
            ->where('uuid', $request->uuid)
            ->first();

        if (!$dispositivo) {
            return response()->json([
                'success' => false,
                'message' => 'Este dispositivo no está registrado para este usuario invitado',
            ], 403);
        }

        // Generar nuevo token
        $token = JWTAuth::fromUser($user);

        // Actualizar último login
        $dispositivo->update(['ultimo_login' => Carbon::now()]);

        return response()->json([
            'success' => true,
            'TOKEN' => $token,
            'ID' => $user->id,
            'NOMBRE' => $user->name,
            'CORREO' => $user->email,
        ], 200);
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
                'permissions' => $user->getAllPermissions()->pluck('name'),
            ]
        ], 200);
    }
}