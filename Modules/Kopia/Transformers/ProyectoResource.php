<?php

namespace Modules\Kopia\Transformers;

use Illuminate\Http\Resources\Json\JsonResource;

class ProyectoResource extends JsonResource
{
    public function toArray($request): array
    {
        $data = [
            'id' => $this->id,
            'uuid_movil' => $this->uuid_movil,
            'lote_id' => $this->lote_id,
            'titulo' => $this->titulo,
            'descripcion' => $this->descripcion,
            'tipo_ensayo' => $this->tipo_ensayo,
            'financiamiento' => $this->financiamiento,
            'colaborador_nombre' => $this->colaborador_nombre,
            'colaborador_telefono' => $this->colaborador_telefono,
            'colaborador_celular' => $this->colaborador_celular,
        ];

        if ($this->relationLoaded('responsable')) {
            $data['responsable'] = $this->responsable->name ?? 'Sin asignar';
        }

        if ($this->relationLoaded('variedades')) {
            $data['variedades_ids'] = $this->variedades->pluck('id')->toArray();
            $data['variedades'] = $this->variedades->map(function ($v) {
                return ($v->cultivo->nombre ?? '') . ' - ' . $v->nombre;
            })->filter()->values();
        }

        if ($this->relationLoaded('lote')) {
            $data['lote'] = [
                'id' => $this->lote->id,
                'nombre' => $this->lote->nombre_lote,
                'provincia' => $this->lote->provincia?->name ?? 'Sin Provincia'
            ];
        }

        if ($this->relationLoaded('ciclos')) {
            $historialVisitas = collect();
            $ciclosCrudos = [];

            foreach ($this->ciclos as $ciclo) {
                $cicloArray = $ciclo->toArray();

                if ($ciclo->relationLoaded('visitas')) {
                    $visitasArray = [];
                    foreach ($ciclo->visitas as $visita) {
                        $hojas = $visita->relationLoaded('hojas_datos') ? $visita->hojas_datos : [];

                        $historialVisitas->push([
                            'id' => $visita->id,
                            'fecha' => $visita->fecha_visita,
                            'tecnico' => $visita->tecnico_nombre,
                            'observaciones' => $visita->observaciones,
                            'recomendaciones' => $visita->recomendaciones,
                            'cantidad_datos' => count($hojas),
                            'plantillas_usadas' => collect($hojas)->pluck('nombre_plantilla')->unique()->values()
                        ]);

                        $visitaArray = $visita->toArray();
                        $visitaArray['hojas_datos'] = $hojas;
                        $visitasArray[] = $visitaArray;
                    }
                    $cicloArray['visitas'] = $visitasArray;
                }
                $ciclosCrudos[] = $cicloArray;
            }

            $data['bitacora_visitas'] = $historialVisitas->sortByDesc('fecha')->values();
            $data['total_visitas'] = $historialVisitas->count();
            $data['ciclos'] = $ciclosCrudos;

            $data['contacto_externo'] = [
                'nombre' => $this->colaborador_nombre ?? 'N/A',
                'telefono' => $this->colaborador_telefono,
                'celular' => $this->colaborador_celular,
            ];
        }

        return $data;
    }
}
