<?php

namespace Modules\Transferencia\Transformers;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OrganizacionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'nombre' => $this->nombre,
            'tipo' => $this->tipo_organizacion,
            'participantes' => [
                'hombres' => $this->participantes_hombres,
                'mujeres' => $this->participantes_mujeres,
                'total' => $this->participantes_hombres + $this->participantes_mujeres,
            ],
            'ubicacion' => [
                'provincia_id' => $this->provincia_id,
                'provincia_nombre' => $this->whenLoaded('provincia', fn() => $this->provincia->name),

                'canton_id' => $this->canton_id,
                'canton_nombre' => $this->whenLoaded('canton', fn() => $this->canton->name),

                'parroquia_id' => $this->parroquia_id,
                'parroquia_nombre' => $this->whenLoaded('parroquia', fn() => $this->parroquia->nombre),
            ],
            'user_id' => $this->user_id,
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
