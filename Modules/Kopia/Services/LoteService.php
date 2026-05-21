<?php

namespace Modules\Kopia\Services;

use Illuminate\Support\Facades\DB;
use Modules\Kopia\Entities\Lote;
use Illuminate\Database\Eloquent\Collection;

class LoteService
{
    /**
     * Obtiene todos los lotes con sus geometrías y relaciones básicas para el mapa.
     */
    public function obtenerTodosLosLotes(array $filtros = []): Collection
    {
        $query = Lote::with(['proyectos.ciclos.visitas.hojasDatos', 'proyectos.variedad.cultivo'])
            ->select(
                '*',
                DB::raw('ST_AsGeoJSON(area) as geometria_geojson')
            );

        if (isset($filtros['province_id'])) {
            $query->where('province_id', $filtros['province_id']);
        }

        return $query->get();
    }

    /**
     * Obtiene el detalle profundo de un lote específico.
     */
    public function obtenerDetalleLote(int $id): \Illuminate\Database\Eloquent\Builder|array|Collection|\Illuminate\Database\Eloquent\Model
    {
        return Lote::with([
            'proyectos.variedad.cultivo',
            'proyectos.responsable',
            'proyectos.ciclos'
        ])->findOrFail($id);
    }

    public function crearLote(array $datos): Lote
    {
        return DB::transaction(function () use ($datos) {
            $attributes = [
                'uuid_movil'       => $datos['uuid_movil'] ?? Str::uuid()->toString(),
                'nombre_lote'      => $datos['nombre_lote'],
                'province_id'      => $datos['province_id'] ?? 1,
                'canton_id'        => $datos['canton_id'] ?? 1,
                'altitud'          => $datos['altitud'] ?? null,
                // Kopia no tiene 'estado' por defecto, pero si lo agregaste a la migración, ponlo aquí
            ];

            // Convertimos las coordenadas (enviadas desde KopiaLoteFormModal) a Polígono PostGIS
            if (!empty($datos['coordenadas']) && is_array($datos['coordenadas'])) {
                $attributes['area'] = DB::raw($this->convertirCoordenadasAPoligono($datos['coordenadas']));
            }

            return Lote::create($attributes);
        });
    }

    public function actualizarLote(int $id, array $datos): Lote
    {
        return DB::transaction(function () use ($id, $datos) {
            $lote = Lote::findOrFail($id);

            if (isset($datos['nombre_lote'])) {
                $lote->nombre_lote = $datos['nombre_lote'];
            }

            // Nota: Por regla de negocio (como indicaste en el modal),
            // no permitimos editar la geometría desde la web si ya viene del móvil.

            $lote->save();
            return $lote;
        });
    }

    public function eliminarLote(int $id): void
    {
        $lote = Lote::findOrFail($id);

        // Protección de Integridad Referencial adaptada a Kopia
        if ($lote->proyectos()->count() > 0) {
            throw new \Exception("Integridad Referencial: No puedes eliminar un Lote que contiene Proyectos/Ensayos activos.");
        }

        $lote->delete();
    }

    // Helper reutilizado de tu SyncKopiaService
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

    /**
     * Crea un Lote y sus Proyectos asociados en una sola transacción (Offline-First approach)
     */
    public function crearLoteIntegrado(array $datos, int $responsableId): Lote
    {
        return DB::transaction(function () use ($datos, $responsableId) {
            // 1. Preparar e insertar Lote
            $loteAttributes = [
                'uuid_movil'       => $datos['uuid_movil'],
                'nombre_lote'      => $datos['nombre_lote'],
                'province_id'      => $datos['province_id'],
                'canton_id'        => $datos['canton_id'],
                'location_id'      => $datos['location_id'] ?? null,
                'ubicacion_manual' => $datos['ubicacion_manual'] ?? null,
                'altitud'          => $datos['altitud'] ?? null,
            ];

            if (!empty($datos['coordenadas']) && is_array($datos['coordenadas'])) {
                $loteAttributes['area'] = DB::raw($this->convertirCoordenadasAPoligono($datos['coordenadas']));
            }

            $lote = Lote::create($loteAttributes);

            // 2. Iterar e insertar Proyectos del lote
            foreach ($datos['proyectos'] as $proyectoData) {
                $proyecto = Proyecto::create([
                    'uuid_movil'     => $proyectoData['uuid_movil'],
                    'lote_id'        => $lote->id,
                    'responsable_id' => $responsableId,
                    'titulo'         => $proyectoData['titulo'],
                    'descripcion'    => $proyectoData['descripcion'] ?? null,
                    'tipo_ensayo'    => $proyectoData['tipo_ensayo'] ?? null,
                ]);

                // Sincronizar relaciones
                if (!empty($proyectoData['variedades_ids'])) {
                    $proyecto->variedades()->sync($proyectoData['variedades_ids']);
                }

                if (!empty($proyectoData['colaboradores'])) {
                    $proyecto->colaboradores()->sync($proyectoData['colaboradores']);
                }
            }

            return $lote;
        });
    }
}
