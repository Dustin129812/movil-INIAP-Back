<?php

namespace Modules\AgroDecide\Transformers;

use Illuminate\Http\Resources\Json\JsonResource;

class CultivoResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'nombre' => $this->nombre,
            'nombre_cientifico' => $this->nombre_cientifico,
            'variedades' => VariedadResource::collection($this->whenLoaded('variedades')),
        ];
    }
}
