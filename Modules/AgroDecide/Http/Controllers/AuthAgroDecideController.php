<?php

namespace Modules\AgroDecide\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Modules\AgroDecide\Http\Requests\LoginAgroDecideRequest;
use Modules\AgroDecide\Services\AuthAgroDecideService;

class AuthAgroDecideController extends Controller
{
    public function __construct(
        private readonly AuthAgroDecideService $authService
    ) {}

    public function login(LoginAgroDecideRequest $request): JsonResponse
    {
        $payload = $this->authService->authenticateMobile($request->validated());

        return response()->json([
            'success' => true,
            'data' => $payload
        ], 200);
    }
}
