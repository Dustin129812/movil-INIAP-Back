<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Tymon\JWTAuth\Exceptions\JWTException;
use Tymon\JWTAuth\Exceptions\TokenExpiredException;
use Tymon\JWTAuth\Exceptions\TokenInvalidException;

class JwtMiddleware
{

    public function handle(Request $request, Closure $next)
    {
        try {

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
                'message' => 'El token ha expirado'
            ], 401);

        } catch (TokenInvalidException $e) {

            return response()->json([
                'success' => false,
                'message' => 'El token es inválido'
            ], 401);

        } catch (JWTException $e) {

            return response()->json([
                'success' => false,
                'message' => 'No se encontró un token de autenticación'
            ], 401);

        } catch (\Exception $e) {

            return response()->json([
                'success' => false,
                'message' => 'Error de autenticación'
            ], 500);
        }

        return $next($request);
    }
}