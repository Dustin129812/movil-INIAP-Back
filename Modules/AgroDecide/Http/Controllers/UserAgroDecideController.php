<?php

namespace Modules\AgroDecide\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\AgroDecide\Services\AuthUserAgroDecideService;

class UserAgroDecideController extends Controller
{
    public function __construct(
        private readonly AuthUserAgroDecideService $authService
    ) {}

    public function login(Request $request): JsonResponse
    {
        $request->validate([
            'correo_institucional' => 'required|email',
            'password' => 'required|string',
        ]);

        $payload = $this->authService->authenticate($request->only('correo_institucional', 'password'));

        return response()->json([
            'success' => true,
            'data' => $payload,
        ], 200);
    }
}
