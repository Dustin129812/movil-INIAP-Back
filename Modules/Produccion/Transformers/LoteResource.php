<?php

namespace Modules\Produccion\Transformers;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class LoteResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'codigo' => $this->codigo,
            'nombre' => $this->nombre,
            'superficie_hectareas' => (float)$this->superficie_hectareas,
            'estado' => $this->estado,
            'observaciones' => $this->observaciones,
            'poligono_geojson' => $this->resource->poligono_geojson,
            'hijos' => LoteResource::collection($this->whenLoaded('hijos')),
            'ubicacion' => $this->whenLoaded('location', function () {
                return [
                    'id' => $this->location->id,
                    'nombre' => $this->location->name,
                ];
            }),

            'fecha_creacion' => $this->created_at->format('Y-m-d H:i:s'),
        ];
    }
}
