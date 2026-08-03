<?php

namespace Modules\AgroDecide\Services;

use Modules\AgroDecide\Entities\DispositivoInvitado;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Tymon\JWTAuth\Facades\JWTAuth;

class GuestAuthService
{
    public function registrarYGenerarToken(array $data): string
    {
        return DB::transaction(function () use ($data) {

            $dispositivo = DispositivoInvitado::firstOrNew(
                ['id' => $data['device_uuid']]
            );

            // Si el dispositivo es nuevo o ya existe, actualizar datos
            $dispositivo->modelo_dispositivo = $data['modelo'] ?? $dispositivo->modelo_dispositivo ?? 'Desconocido';
            $dispositivo->sistema_operativo = $data['sistema_operativo'] ?? $dispositivo->sistema_operativo;
            $dispositivo->hardware = $data['hardware'] ?? $dispositivo->hardware;
            $dispositivo->ultimo_login = Carbon::now();

            // Solo asignar estado 'activo' si es nuevo
            if (!$dispositivo->exists) {
                $dispositivo->estado = 'activo';
            }

            $dispositivo->save();

            // Verificar si el dispositivo está bloqueado
            if ($dispositivo->estado && $dispositivo->estado !== 'activo') {
                abort(403, 'Dispositivo bloqueado por seguridad.');
            }

            // Generar payload JWT
            $payload = [
                'sub'         => $dispositivo->id,
                'role'        => 'guest',
                'device_uuid' => $dispositivo->id,
                'iat'         => time(),
                'exp'         => time() + (43200 * 60) // 30 días
            ];

            $token = JWTAuth::getJWTProvider()->encode($payload);

            return $token;
        });
    }
}
