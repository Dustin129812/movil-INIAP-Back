<?php

namespace Modules\Transferencia\Transformers;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class EnsayoResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'nombre' => $this->nombre,
            'tipo' => $this->tipo,
            'estado' => $this->estado,
            'cantidad_parcelas' => $this->whenCounted('parcelas'),
            'tecnologia' => [
                'nombre' => $this->nombre_tecnologia,
                'tipo' => $this->tipo_tecnologia,
            ],
            'archivos' => [
                'tiene_protocolo' => $this->tiene_protocolo,
                'aprobado_por_comite' => $this->aprobado_por_comite,
                'fecha_aprobacion' => $this->fecha_aprobacion_protocolo?->format('Y-m-d'),

                'protocolo_url' => $this->archivo_protocolo_path
                    ? route('api.transferencia.archivos.descargar', ['tipo' => 'protocolo', 'id' => $this->id])
                    : null,
                'informe_url' => $this->archivo_informe_path
                    ? route('api.transferencia.archivos.descargar', ['tipo' => 'informe', 'id' => $this->id])
                    : null,
            ],
            'poa' => [
                'producto' => $this->whenLoaded('producto', fn() => [
                    'id' => $this->producto->id,
                    'nombre' => $this->producto->name,
                ]),
                'actividad' => $this->whenLoaded('actividad', fn() => [
                    'id' => $this->actividad->id,
                    'descripcion' => $this->actividad->description,
                ]),
            ],
            'creador' => $this->whenLoaded('user', function () {
                return [
                    'id' => $this->user->id,
                    'name' => $this->user->name,
                ];
            }),
            'location_id' => $this->location_id,
            'equipo_tecnico' => $this->whenLoaded('equipoTecnico'),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
