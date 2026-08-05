<?php

namespace Modules\AgroDecide\Http\Middleware;

use Closure;
use Exception;
use Illuminate\Http\Request;
use Tymon\JWTAuth\Facades\JWTAuth;
use Modules\AgroDecide\Entities\DispositivoInvitado;

class ValidateAgroDecideToken
{
    public function handle(Request $request, Closure $next)
    {
        try {
            $payload = JWTAuth::parseToken()->getPayload();

            $role = $payload->get('role');

            if ($role === 'guest') {
                $deviceUuid = $payload->get('device_uuid');

                $dispositivo = DispositivoInvitado::find($deviceUuid);
                if (!$dispositivo || $dispositivo->estado !== 'activo') {
                    return response()->json(['error' => 'Dispositivo bloqueado o no registrado.'], 403);
                }

                return $next($request);
            }

            if ($role === 'user') {
                // Token de usuario válido, solo verificar que existe el claim sub
                $userId = $payload->get('sub');
                if (!$userId) {
                    return response()->json(['error' => 'Token inválido.'], 401);
                }
                return $next($request);
            }

            return response()->json(['error' => 'Rol no autorizado.'], 403);

        } catch (Exception $e) {
            return response()->json(['error' => 'Token inválido o no autorizado.'], 401);
        }
    }
}
