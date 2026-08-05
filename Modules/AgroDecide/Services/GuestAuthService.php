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
                $dispositivo->sistema_operativo = $data['sistema_operativo'] ?? null;
                $dispositivo->hardware = $data['hardware'] ?? null;
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
                'modelo'      => $dispositivo->modelo_dispositivo,
                'sistema_operativo' => $dispositivo->sistema_operativo,
                'hardware'    => $dispositivo->hardware,
                'iat'         => time(),
                'exp'         => time() + (43200 * 60) // 30 días
            ];

            $token = JWTAuth::getJWTProvider()->encode($payload);

            return $token;
        });
    }
}
