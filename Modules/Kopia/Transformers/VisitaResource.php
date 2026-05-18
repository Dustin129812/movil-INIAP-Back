<?php

namespace Modules\Kopia\Transformers;

use Illuminate\Http\Resources\Json\JsonResource;

class VisitaResource extends JsonResource {
    public function toArray($request): array {
        return [
            'id' => $this->uuid_movil,
            'tecnico' => $this->tecnico_nombre,
            'fecha' => $this->fecha_visita,
            'observaciones' => $this->observaciones,
            'recomendaciones' => $this->recomendaciones,
            'datos_tecnicos' => HojaDatoResource::collection($this->whenLoaded('hojas')),
        ];
    }
}
