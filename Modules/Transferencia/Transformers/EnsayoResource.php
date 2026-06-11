<?php

namespace Modules\Transferencia\Transformers;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\URL;

class EnsayoResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $protocolosPaths = is_array($this->archivo_protocolo_path)
            ? $this->archivo_protocolo_path
            : json_decode($this->archivo_protocolo_path ?? '[]', true) ?? [];

        $informesPaths = is_array($this->archivo_informe_path)
            ? $this->archivo_informe_path
            : json_decode($this->archivo_informe_path ?? '[]', true) ?? [];

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
                'tiene_protocolo' => (bool) $this->tiene_protocolo,
                'aprobado_por_comite' => (bool) $this->aprobado_por_comite,
                'fecha_aprobacion' => $this->fecha_aprobacion_protocolo?->format('Y-m-d'),

                'protocolos_urls' => collect($protocolosPaths)->map(function ($path, $index) {
                    return URL::temporarySignedRoute(
                        'api.transferencia.ensayos.download',
                        now()->addMinutes(30),
                        ['ensayo' => $this->id, 'index' => $index]
                    );
                })->toArray(),

                'informes_urls' => collect($informesPaths)->map(function ($path, $index) {
                    return URL::temporarySignedRoute(
                        'api.transferencia.ensayos.download',
                        now()->addMinutes(30),
                        ['ensayo' => $this->id, 'index' => $index]
                    );
                })->toArray(),

                'protocolos_paths' => $protocolosPaths,
                'informes_paths' => $informesPaths,
            ],
            'poa' => [
                'producto_id' => $this->producto_id,
                'actividad_id' => $this->actividad_id,
                'producto' => $this->whenLoaded('producto', fn() => [
                    'id' => $this->producto->id,
                    'nombre' => $this->producto->name,
                ]),
                'actividad' => $this->whenLoaded('actividad', fn() => [
                    'id' => $this->actividad->id,
                    'descripcion' => $this->actividad->description,
                ]),
            ],
            'user_id' => $this->user_id,
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
