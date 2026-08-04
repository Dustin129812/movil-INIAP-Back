<?php

namespace Modules\Administracion\Transformers;

use Illuminate\Http\Resources\Json\JsonResource;

class AdminDashboardResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'system_users'     => $this->resource['usuarios_activos'],
            'system_roles'     => $this->resource['roles_sistema'],
            'db_connections'   => $this->resource['consultas_bd'],
            'server_health'    => $this->resource['salud_server'],
            'recent_activity'  => $this->resource['actividad_reciente'],
        ];
    }
}
