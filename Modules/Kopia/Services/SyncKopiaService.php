<?php

namespace Modules\Kopia\Services;

use Illuminate\Support\Facades\DB;
use Modules\Kopia\Entities\Lote;
use Modules\Kopia\Entities\Proyecto;
use Modules\Kopia\Entities\CicloCultivo;
use Modules\Kopia\Entities\Visita;
use Modules\Kopia\Entities\HojaDato;
use Modules\Kopia\Transformers\LoteResource;

class SyncKopiaService
{
    public function procesarSincronizacion(array $lotesData): array
    {
        $resultados = [
            'lotes_procesados' => 0,
            'proyectos_procesados' => 0,
            'ciclos_procesados' => 0,
            'visitas_procesadas' => 0,
            'hojas_procesadas' => 0,
        ];

        DB::transaction(function () use ($lotesData, &$resultados) {
            foreach ($lotesData as $loteData) {
                $lote = $this->guardarLote($loteData);
                $resultados['lotes_procesados']++;

                if (!empty($loteData['proyectos'])) {
                    foreach ($loteData['proyectos'] as $proyectoData) {
                        $proyecto = $this->guardarProyecto($lote, $proyectoData);
                        $resultados['proyectos_procesados']++;

                        if (!empty($proyectoData['ciclos'])) {
                            foreach ($proyectoData['ciclos'] as $cicloData) {
                                $ciclo = $this->guardarCiclo($lote, $cicloData, $proyecto->id);
                                $resultados['ciclos_procesados']++;

                                if (!empty($cicloData['visitas'])) {
                                    foreach ($cicloData['visitas'] as $visitaData) {
                                        $visita = $this->guardarVisita($ciclo, $proyecto, $visitaData);
                                        $resultados['visitas_procesadas']++;

                                        if (!empty($visitaData['hojas_datos'])) {
                                            foreach ($visitaData['hojas_datos'] as $hojaData) {
                                                $this->guardarHojaDato($visita, $hojaData);
                                                $resultados['hojas_procesadas']++;
                                            }
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
            }
        });

        return $resultados;
    }

    private function guardarCiclo(Lote $lote, array $data, int $proyectoId): CicloCultivo
    {
        return CicloCultivo::updateOrCreate(
            ['uuid_movil' => $data['uuid_movil']],
            [
                'lote_id' => $lote->id,
                'proyecto_id' => $proyectoId,
                'cultivo_variedad' => $data['cultivo_variedad'],
                'distancia_siembra' => $data['distancia_siembra'],
                'fecha_siembra' => $data['fecha_siembra'],
                'metricas_siembra'  => $data['metricas_siembra'] ?? null,
                'es_actual' => true,
            ]
        );
    }

    // Actualizamos la firma de guardarVisita
    private function guardarVisita(CicloCultivo $ciclo, Proyecto $proyecto, array $data): Visita
    {
        return Visita::updateOrCreate(
            ['uuid_movil' => $data['uuid_movil']],
            [
                'ciclo_cultivo_id' => $ciclo->id,
                'proyecto_id' => $proyecto->id,
                'tecnico_nombre' => $data['tecnico_nombre'],
                'fecha_visita' => $data['fecha_visita'],
                'observaciones' => $data['observaciones'] ?? null,
                'recomendaciones' => $data['recomendaciones'] ?? null,
            ]
        );
    }

    private function guardarLote(array $data): Lote
    {
        $attributes = [
            'nombre_lote'      => $data['nombre_lote'],
            'ubicacion_manual' => $data['ubicacion_manual'] ?? null,
            'province_id'      => $data['province_id'],
            'canton_id'        => $data['canton_id'],
            'location_id'      => $data['location_id'] ?? null,
            'parroquia'        => $data['parroquia'] ?? null,
            'altitud'          => $data['altitud'] ?? null,
            'otros_datos_geo'  => $data['otros_datos_geo'] ?? null,
            'condiciones_terreno' => $data['condiciones_terreno'] ?? null,
        ];

        if (!empty($data['coordenadas']) && is_array($data['coordenadas'])) {
            $attributes['area'] = DB::raw($this->convertirCoordenadasAPoligono($data['coordenadas']));
        }

        return Lote::updateOrCreate(
            ['uuid_movil' => $data['uuid_movil']],
            $attributes
        );
    }

    /**
     * Obtiene el payload de lotes y proyectos filtrado estrictamente
     * para el usuario autenticado (responsable o colaborador).
     */
    public function obtenerDatosSincronizacion(int $userId): array
    {
        $lotes = Lote::whereHas('proyectos', function ($query) use ($userId) {
            $query->where('responsable_id', $userId)
                ->orWhereHas('colaboradores', function ($q) use ($userId) {
                    $q->where('user_id', $userId);
                });
        })
            ->with([
                'provincia',
                'canton',
                'estacion',
                'proyectos' => function ($query) use ($userId) {
                    $query->where('responsable_id', $userId)
                        ->orWhereHas('colaboradores', function ($q) use ($userId) {
                            $q->where('user_id', $userId);
                        })
                        ->with([
                            'variedad.cultivo',
                            'responsable',
                            'colaboradores',
                            'ciclos' => function ($q) {
                                $q->with(['visitas.hojasDatos']);
                            }
                        ]);
                }
            ])
            ->get();

        return [
            'lotes' => LoteResource::collection($lotes)->resolve(),
        ];
    }

    private function guardarProyecto(Lote $lote, array $data): Proyecto
    {
        $proyecto = Proyecto::updateOrCreate(
            ['uuid_movil' => $data['uuid_movil']],
            [
                'lote_id' => $lote->id,
                'responsable_id' => $data['responsable_id'],
                'titulo' => $data['titulo'],
                'descripcion' => $data['descripcion'] ?? null,
                'tipo_ensayo' => $data['tipo_ensayo'] ?? null,
                'financiamiento' => $data['financiamiento'] ?? null,
                'colaborador_nombre' => $data['colaborador_nombre'] ?? null,
                'colaborador_telefono' => $data['colaborador_telefono'] ?? null,
                'colaborador_celular' => $data['colaborador_celular'] ?? null,
            ]
        );

        if (!empty($data['variedades_ids'])) {
            $proyecto->variedades()->sync($data['variedades_ids']);
        }

        return $proyecto;
    }

    private function guardarHojaDato(Visita $visita, array $data): HojaDato
    {
        return HojaDato::updateOrCreate(
            ['uuid_movil' => $data['uuid_movil']],
            [
                'visita_id' => $visita->id,
                'nombre_plantilla' => $data['nombre_plantilla'],
                'datos_variables' => $data['datos_variables'],
            ]
        );
    }

    private function convertirCoordenadasAPoligono(array $coordenadas): string
    {
        $puntos = collect($coordenadas)->map(function ($punto) {
            return "{$punto['longitude']} {$punto['latitude']}";
        });

        $primerPunto = $puntos->first();
        if ($puntos->last() !== $primerPunto) {
            $puntos->push($primerPunto);
        }

        $wkt = $puntos->implode(', ');
        return "ST_GeomFromText('POLYGON(({$wkt}))', 4326)";
    }
}
