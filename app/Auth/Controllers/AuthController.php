<?php

namespace App\Auth\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Auth\Requests\LoginRequest;
use App\Auth\Services\AuthService;

class AuthController extends Controller
{
    public function __construct(
        private AuthService $authService
    ){}

    public function register(Request $request)
    {
        return $this->authService->register($request);
    }

    public function login(LoginRequest $request)
    {
        return $this->authService->login($request);
    }

    public function me()
    {
        return $this->authService->me();
    }

    public function logout()
    {
        return $this->authService->logout();
    }

    public function refresh(Request $request)
    {
        return $this->authService->refresh($request);
    }
}