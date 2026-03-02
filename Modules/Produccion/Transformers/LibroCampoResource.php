<?php

namespace Modules\Produccion\Transformers;

use Illuminate\Http\Resources\Json\JsonResource;

class LibroCampoResource extends JsonResource
{
    public function toArray($request): array
    {
        $costoInsumos = (float) $this->actividades()->sum('costo_actividad');
        $costoPersonal = (float) $this->actividadesPersonal()->sum('costo_total');
        $costoMaquinaria = (float) $this->actividadesMaquinaria()->sum('costo_total');

        return [
            'id'             => $this->id,
            'historial_climatico' => $this->whenLoaded('registrosClimaticos'),
            'codigo'         => $this->codigo,
            'qr_token'       => $this->qr_token,
            'nombre'         => $this->nombre,
            'estado'         => $this->estado,
            'fecha_inicio'   => $this->fecha_inicio,

            'resumen_costos' => [
                'insumos'       => round($costoInsumos, 2),
                'mano_de_obra'  => round($costoPersonal, 2),
                'maquinaria'    => round($costoMaquinaria, 2),
                'total_general' => round($costoInsumos + $costoPersonal + $costoMaquinaria, 2),
            ],

            'lote'        => new LoteResource($this->whenLoaded('lote')),
            'actividades' => $this->whenLoaded('actividades'),
            'personal'    => $this->whenLoaded('actividadesPersonal'),
            'maquinaria'  => $this->whenLoaded('actividadesMaquinaria'),
        ];
    }
}
