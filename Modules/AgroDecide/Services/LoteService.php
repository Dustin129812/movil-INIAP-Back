<?php

namespace Modules\AgroDecide\Services;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Modules\AgroDecide\Entities\Lote;
use Modules\AgroDecide\Entities\Proyecto;

class LoteService
{
    /**
     * Obtiene el detalle profundo de un lote específico.
     */
    public function obtenerDetalleLote(int $id): \Illuminate\Database\Eloquent\Builder|array|Collection|\Illuminate\Database\Eloquent\Model
    {
        return Lote::with([
            'proyectos.responsable',
            'proyectos.ciclos'
        ])->findOrFail($id);
    }

    public function actualizarLote(int $id, array $datos): Lote
    {
        return DB::transaction(function () use ($id, $datos) {
            $lote = Lote::findOrFail($id);

            if (isset($datos['nombre_lote'])) {
                $lote->nombre_lote = $datos['nombre_lote'];
            }

            $lote->save();
            return $lote;
        });
    }

    public function eliminarLote(int $id): void
    {
        $lote = Lote::findOrFail($id);

        if ($lote->proyectos()->count() > 0) {
            throw new \Exception("Integridad Referencial: No puedes eliminar un Lote que contiene Proyectos/Ensayos activos.");
        }

        $lote->delete();
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

    /**
     * Crea un Lote y sus Proyectos asociados en una sola transacción (Offline-First approach)
     */
    public function crearLoteIntegrado(array $datos, int|string $responsableId, string $role): Lote
    {
        return DB::transaction(function () use ($datos, $responsableId, $role) {
            $loteAttributes = [
                'uuid_movil'       => $datos['uuid_movil'],
                'nombre_lote'      => $datos['nombre_lote'],
                'province_id'      => $datos['province_id'],
                'canton_id'        => $datos['canton_id'],
                'location_id'      => $datos['location_id'] ?? null,
                'ubicacion_manual' => $datos['ubicacion_manual'] ?? null,
                'altitud'          => $datos['altitud'] ?? null,
            ];

            // Asignar según el rol (user o guest)
            if ($role === 'user') {
                $loteAttributes['user_agrodecide_id'] = (int) $responsableId;
            } else {
                $loteAttributes['dispositivo_invitado_id'] = $responsableId;
            }

            if (!empty($datos['coordenadas']) && is_array($datos['coordenadas'])) {
                $loteAttributes['area'] = DB::raw($this->convertirCoordenadasAPoligono($datos['coordenadas']));
            }

            $lote = Lote::create($loteAttributes);

            foreach ($datos['proyectos'] as $proyectoData) {
                $colaboradores = $proyectoData['colaboradores'] ?? null;

                $proyectoAttrs = [
                    'uuid_movil'     => $proyectoData['uuid_movil'],
                    'lote_id'        => $lote->id,
                    'titulo'         => $proyectoData['titulo'],
                    'descripcion'    => $proyectoData['descripcion'] ?? null,
                    'tipo_ensayo'    => $proyectoData['tipo_ensayo'] ?? null,
                    'variedad'       => $proyectoData['variedad'],
                    'fecha_siembra'  => $proyectoData['fecha_siembra'] ?? null,
                    'tipo_acolchado' => $proyectoData['tipo_acolchado'] ?? null,
                ];

                // Asignar según el rol
                if ($role === 'user') {
                    $proyectoAttrs['responsable_id'] = (int) $responsableId;
                } else {
                    $proyectoAttrs['dispositivo_invitado_id'] = $responsableId;
                }

                $proyecto = Proyecto::create($proyectoAttrs);

                if (!empty($colaboradores)) {
                    $proyecto->colaboradores()->sync($colaboradores);
                }
            }

            return $lote;
        });
    }
}
