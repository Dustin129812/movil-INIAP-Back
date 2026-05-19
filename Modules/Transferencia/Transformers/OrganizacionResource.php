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
                // CAMBIO AQUÍ: Usamos ->name en lugar de ->nombre
                'provincia_nombre' => $this->whenLoaded('provincia', fn() => $this->provincia->name),

                'canton_id' => $this->canton_id,
                // CAMBIO AQUÍ: Usamos ->name en lugar de ->nombre
                'canton_nombre' => $this->whenLoaded('canton', fn() => $this->canton->name),

                // Agregamos el parroquia_id para que el formulario React lo reconozca al editar
                'parroquia_id' => $this->parroquia_id,
                // Este se queda como ->nombre porque así está en la migración de parroquias
                'parroquia_nombre' => $this->whenLoaded('parroquia', fn() => $this->parroquia->nombre),
            ],
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
