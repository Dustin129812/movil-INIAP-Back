<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use PHPOpenSourceSaver\JWTAuth\Exceptions\JWTException;
use PHPOpenSourceSaver\JWTAuth\Exceptions\TokenExpiredException;
use PHPOpenSourceSaver\JWTAuth\Exceptions\TokenInvalidException;

class JwtMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        try {

            // Verifica que exista un token válido
            $user = Auth::guard('api')->user();

            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'Usuario no autenticado'
                ], 401);
            }

        } catch (TokenExpiredException $e) {

            return response()->json([
                'success' => false,
                'message' => 'Token expirado'
            ], 401);


        } catch (TokenInvalidException $e) {

            return response()->json([
                'success' => false,
                'message' => 'Token inválido'
            ], 401);


        } catch (JWTException $e) {

            return response()->json([
                'success' => false,
                'message' => 'Token no encontrado'
            ], 401);
        }


        return $next($request);
    }
}