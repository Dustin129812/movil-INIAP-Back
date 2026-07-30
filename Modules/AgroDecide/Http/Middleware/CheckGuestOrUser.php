<?php

namespace Modules\AgroDecide\Http\Middleware;

use Closure;
use Exception;
use Illuminate\Http\Request;
use Tymon\JWTAuth\Facades\JWTAuth;
use Modules\AgroDecide\Entities\DispositivoInvitado;

class CheckGuestOrUser
{
    public function handle(Request $request, Closure $next)
    {
        try {
            $payload = JWTAuth::parseToken()->getPayload();

            if ($payload->get('role') === 'guest') {
                $deviceUuid = $payload->get('device_uuid');

                $dispositivo = DispositivoInvitado::find($deviceUuid);
                if (!$dispositivo || $dispositivo->estado !== 'activo') {
                    return response()->json(['error' => 'Dispositivo bloqueado o no registrado.'], 403);
                }

                return $next($request);
            }

            JWTAuth::parseToken()->authenticate();

        } catch (Exception $e) {
            return response()->json(['error' => 'Token inválido o no autorizado.'], 401);
        }

        return $next($request);
    }
}
