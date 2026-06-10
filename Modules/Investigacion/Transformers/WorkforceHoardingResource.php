<?php

namespace Modules\Investigacion\Transformers;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class WorkforceHoardingResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'tecnico' => [
                'id'     => $this->technician_id,
                'nombre' => $this->technician_name,
            ],
            'metricas' => [
                'personal_unico_solicitado'  => (int) $this->unique_workers_requested,
                'jornadas_totales_pedidas'   => (int) $this->total_support_days_requested,
                'eficiencia_promedio_tareas' => (float) $this->average_activity_compliance,
                'obrero_mas_repetido' => [
                    'nombre' => $this->most_requested_worker_name,
                    'usos'   => (int) $this->most_requested_worker_count,
                ],
                'desglose_obreros' => $this->workers_breakdown ?? []
            ]
        ];
    }
}
