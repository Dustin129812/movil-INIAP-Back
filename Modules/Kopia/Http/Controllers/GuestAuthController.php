<?php

namespace Modules\Kopia\Http\Controllers;

use App\Http\Controllers\Controller;
use Modules\Kopia\Http\Requests\GuestLoginRequest;
use Modules\Kopia\Services\GuestAuthService;

class GuestAuthController extends Controller
{
    protected GuestAuthService $guestAuthService;

    public function __construct(GuestAuthService $guestAuthService)
    {
        $this->guestAuthService = $guestAuthService;
    }

    public function login(GuestLoginRequest $request)
    {
        $validated = $request->validated();

        $token = $this->guestAuthService->registrarYGenerarToken($validated);

        return response()->json([
            'access_token' => $token,
            'token_type'   => 'bearer',
            'expires_in'   => auth('api')->factory()->getTTL() * 60,
            'role'         => 'guest'
        ]);
    }
}
