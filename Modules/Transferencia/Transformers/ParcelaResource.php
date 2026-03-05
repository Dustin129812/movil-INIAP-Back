<?php

namespace Modules\Transferencia\Transformers;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ParcelaResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'nombre' => $this->nombre,
            'estado' => $this->estado,

            // Agrupamos las relaciones maestras
            'referencias' => [
                'ensayo_id' => $this->ensayo_id,
                'ensayo_nombre' => $this->whenLoaded('ensayo', fn() => $this->ensayo->nombre),

                'organizacion_id' => $this->organizacion_id,
                'organizacion_nombre' => $this->whenLoaded('organizacion', fn() => $this->organizacion->nombre),

                'acuerdo_id' => $this->acuerdo_id,
                'libro_campo_id' => $this->libro_campo_id,
            ],

            // Agrupamos la ubicación
            'ubicacion' => [
                'provincia_id' => $this->provincia_id,
                'provincia_nombre' => $this->whenLoaded('provincia', fn() => $this->provincia->nombre ?? $this->provincia->name),

                'canton_id' => $this->canton_id,
                'canton_nombre' => $this->whenLoaded('canton', fn() => $this->canton->nombre ?? $this->canton->name),

                'parroquia_id' => $this->parroquia_id,
                'localidad' => $this->localidad,

                'coordenadas' => [
                    'x' => $this->coordenada_x,
                    'y' => $this->coordenada_y,
                ]
            ],

            // Agrupamos el ciclo de vida
            'fechas' => [
                'implementacion' => $this->fecha_implementacion?->format('Y-m-d'),
                'finalizacion' => $this->fecha_finalizacion?->format('Y-m-d'),
            ],

            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
