<?php

namespace Modules\TrlImporter\Transformers;

use Illuminate\Http\Resources\Json\JsonResource;

class RespuestaResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id'       => $this->id,
            'cumple'   => $this->cumple,
            'pregunta' => $this->whenLoaded('criterio', function() {
                return $this->criterio->criterio_texto;
            }),
            'es_critico' => $this->whenLoaded('criterio', function() {
                return $this->criterio->es_critico;
            }),
        ];
    }
}
