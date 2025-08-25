<?php
// app/Http/Middleware/AuthenticateJWT.php
namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Tymon\JWTAuth\Facades\JWTAuth;

class AuthenticateJWT
{
    public function handle(Request $request, Closure $next)
    {
        try {
            if ($user = JWTAuth::parseToken()->authenticate()) {
                auth()->setUser($user);
            }
        } catch (\Exception $e) {
            // Si no hay token válido, tratar como invitado
            session(['guest_id' => session('guest_id', \Illuminate\Support\Str::uuid()->toString())]);
        }

        return $next($request);
    }
}
