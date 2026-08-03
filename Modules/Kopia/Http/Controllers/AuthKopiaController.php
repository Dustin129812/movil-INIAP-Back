<?php

namespace Modules\Kopia\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Modules\Kopia\Http\Requests\LoginKopiaRequest;
use Modules\Kopia\Services\AuthKopiaService;

class AuthKopiaController extends Controller
{
    public function __construct(
        private readonly AuthKopiaService $authService
    ) {}

    public function login(LoginKopiaRequest $request): JsonResponse
    {
        $payload = $this->authService->authenticateMobile($request->validated());

        return response()->json([
            'success' => true,
            'data' => $payload
        ], 200);
    }
}
