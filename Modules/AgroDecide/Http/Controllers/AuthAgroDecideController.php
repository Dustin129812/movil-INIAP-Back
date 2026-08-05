<?php

namespace Modules\AgroDecide\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Modules\AgroDecide\Http\Requests\LoginAgroDecideRequest;
use Modules\AgroDecide\Services\AuthAgroDecideService;

class AuthAgroDecideController extends Controller
{
    public function __construct(
        private readonly AuthAgroDecideService $authService
    ) {}

    public function login(LoginAgroDecideRequest $request): JsonResponse
    {
        $result = $this->authService->authenticateMobile($request->validated());

        return response()->json([
            'success' => true,
            'data' => $result,
        ], 200);
    }

    public function logout(): JsonResponse
    {
        Auth::guard('api')->logout();

        return response()->json([
            'success' => true,
            'message' => 'Sesión cerrada correctamente.',
        ]);
    }
}
