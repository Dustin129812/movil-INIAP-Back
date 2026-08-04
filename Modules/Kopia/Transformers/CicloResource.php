<?php

namespace Modules\Kopia\Transformers;

use Illuminate\Http\Resources\Json\JsonResource;

class CicloResource extends JsonResource {
    public function toArray($request): array {
        return [
            'id' => $this->uuid_movil,
            'cultivo' => $this->cultivo_variedad,
            'distancia_siembra' => $this->distancia_siembra,
            'fechas' => [
                'siembra' => $this->fecha_siembra->format('Y-m-d'),
                'fin' => $this->fecha_fin ? $this->fecha_fin->format('Y-m-d') : null,
            ],
            'estado_actual' => $this->es_actual,
            'historial_visitas' => VisitaResource::collection($this->whenLoaded('visitas')),
        ];
    }
}
