<?php

namespace Modules\AgroDecide\Services;

use Modules\AgroDecide\Entities\DispositivoInvitado;
use Illuminate\Support\Facades\DB;
use Tymon\JWTAuth\Facades\JWTAuth;

class GuestAuthService
{
    public function registrarYGenerarToken(array $data): string
    {
        return DB::transaction(function () use ($data) {

            $dispositivo = DispositivoInvitado::firstOrNew(
                ['id' => $data['device_uuid']]
            );

            if (!$dispositivo->exists) {
                $dispositivo->modelo_dispositivo = $data['modelo'] ?? 'Desconocido';
                $dispositivo->estado = 'activo';
                $dispositivo->save();
            }

            if ($dispositivo->estado && $dispositivo->estado !== 'activo') {
                abort(403, 'Dispositivo bloqueado por seguridad.');
            }

            $payload = [
                'sub'         => $dispositivo->id,
                'role'        => 'guest',
                'device_uuid' => $dispositivo->id,
                'iat'         => time(),
                'exp'         => time() + (43200 * 60) // 30 días o el tiempo que prefieras
            ];

            $token = JWTAuth::getJWTProvider()->encode($payload);

            return $token;
        });
    }
}
