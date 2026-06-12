<?php

namespace Modules\Kopia\Transformers;

use Carbon\Carbon;
use Illuminate\Http\Resources\Json\JsonResource;

class LoteResource extends JsonResource
{
    public function toArray($request): array
    {
        $ultimaVisita = null;
        if ($this->relationLoaded('proyectos')) {
            $fechas = $this->proyectos
                ->pluck('ciclos')->flatten()
                ->pluck('visitas')->flatten()
                ->pluck('fecha_visita')
                ->filter();

            if ($fechas->isNotEmpty()) {
                $ultimaVisita = $fechas->max();
            }
        }

        $diasSinVisita = null;
        if ($ultimaVisita) {
            $diasSinVisita = round(Carbon::parse($ultimaVisita)->startOfDay()->diffInDays(now()->startOfDay()));
        }

        $variablesDinamicas = [];
        if ($this->relationLoaded('proyectos')) {
            $ultimaVisitaObj = $this->proyectos
                ->pluck('ciclos')->flatten()
                ->pluck('visitas')->flatten()
                ->sortByDesc('fecha_visita')
                ->first();

            if ($ultimaVisitaObj && $ultimaVisitaObj->hojasDatos) {
                foreach ($ultimaVisitaObj->hojasDatos as $hoja) {
                    $datos = is_string($hoja->datos_variables) ? json_decode($hoja->datos_variables, true) : $hoja->datos_variables;

                    if (is_array($datos)) {
                        foreach ($datos as $key => $value) {
                            if (is_numeric($value)) {
                                $cleanKey = ucwords(str_replace('_', ' ', $key));
                                $variablesDinamicas[$cleanKey] = (float) $value;
                            }
                        }
                    }
                }
            }
        }

        return [
            'id' => $this->id,
            'uuid_movil' => $this->uuid_movil,
            'nombre_lote' => $this->nombre_lote,
            'tipo_riego' => $this->tipo_riego ?? 'gravedad',
            'provincia' => $this->provincia?->name ?? 'Sin Provincia',
            'canton'    => $this->canton?->name ?? 'Sin Cantón',
            'parroquia' => $this->parroquia ?? 'Sector no definido',
            'altitud' => $this->altitud,
            'estado' => $this->estado ?? 'PREPARACION',
            'geometria' => $this->geometria_geojson ? json_decode($this->geometria_geojson) : null,
            'ultima_visita' => $ultimaVisita,
            'dias_sin_visita' => $diasSinVisita,
            'metricas_campo' => $variablesDinamicas,
            'proyectos' => $this->whenLoaded('proyectos', function() {
                return $this->proyectos->map(fn($p) => [
                    'id' => $p->id,
                    'uuid_movil' => $p->uuid_movil, // <-- CLAVE: Faltaba esto
                    'responsable_id' => $p->responsable_id,
                    'titulo' => $p->titulo,
                    'descripcion' => $p->descripcion,
                    'cultivo' => $p->variedad?->cultivo?->nombre ?? 'Sin Cultivo Asignado',
                    'variedades_ids' => $p->variedades ? $p->variedades->pluck('id')->toArray() : [],
                    'ciclos' => $p->ciclos->map(fn($c) => [
                        'id' => $c->id,
                        'uuid_movil' => $c->uuid_movil, // <-- CLAVE: Faltaba esto
                        'cultivo_variedad' => $c->cultivo_variedad,
                        'distancia_siembra' => $c->distancia_siembra,
                        'fecha_siembra' => $c->fecha_siembra,
                        'metricas_siembra' => $c->metricas_siembra,
                        'visitas' => $c->visitas->map(fn($v) => [
                            'id' => $v->id,
                            'uuid_movil' => $v->uuid_movil, // <-- CLAVE: Faltaba esto
                            'fecha_visita' => $v->fecha_visita,
                            'tecnico_nombre' => $v->tecnico_nombre,
                            'observaciones' => $v->observaciones,
                            'recomendaciones' => $v->recomendaciones,
                            'hojas_datos' => $v->hojasDatos ? $v->hojasDatos->map(fn($h) => [
                                'id' => $h->id,
                                'uuid_movil' => $h->uuid_movil, // <-- CLAVE: Faltaba esto
                                'nombre_plantilla' => $h->nombre_plantilla,
                                'datos_variables' => $h->datos_variables
                            ]) : []
                        ])
                    ])
                ]);
            }),

            'fecha_creacion' => $this->created_at->format('Y-m-d H:i:s'),
        ];
    }
}
