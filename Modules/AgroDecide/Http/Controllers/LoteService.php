<?php

namespace Modules\AgroDecide\Http\Controllers;

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

            if (isset($datos['estado_verificacion'])) {
                $lote->estado_verificacion = $datos['estado_verificacion'];
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
        // El frontend envía coordenadas como {latitude, longitude} (objetos JSON)
        // PHP decodifica estos como arrays asociativos ['latitude' => x, 'longitude' => y]
        $puntos = collect($coordenadas)->map(function ($punto) {
            // Array con índices numéricos: [longitude, latitude] (GeoJSON)
            if (is_array($punto) && isset($punto[0], $punto[1]) && is_numeric($punto[0])) {
                return "{$punto[0]} {$punto[1]}";
            }
            // Array asociativo: ['latitude' => y, 'longitude' => x] (formato JavaScript)
            if (is_array($punto) && isset($punto['latitude'], $punto['longitude'])) {
                return "{$punto['longitude']} {$punto['latitude']}";
            }
            // Objeto stdClass: (object){latitude: y, longitude: x}
            if (is_object($punto) && isset($punto->latitude, $punto->longitude)) {
                return "{$punto->longitude} {$punto->latitude}";
            }
            return $punto;
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
     *
     * @param array $datos Datos del lote y proyectos
     * @param int|null $responsableId ID del usuario responsable (para usuarios normales)
     * @param string|null $dispositivoInvitadoId UUID del dispositivo invitado (para invitados)
     */
    public function crearLoteIntegrado(array $datos, ?int $responsableId = null, ?string $dispositivoInvitadoId = null): Lote
    {
        return DB::transaction(function () use ($datos, $responsableId, $dispositivoInvitadoId) {
            $loteAttributes = [
                'uuid_movil'       => $datos['uuid_movil'],
                'nombre_lote'      => $datos['nombre_lote'],
                'province_id'      => $datos['province_id'],
                'canton_id'        => $datos['canton_id'],
                'location_id'      => $datos['location_id'] ?? null,
                'ubicacion_manual' => $datos['ubicacion_manual'] ?? null,
                'altitud'          => $datos['altitud'] ?? null,
            ];

            // Si es usuario invitado, guardar el device UUID
            if ($dispositivoInvitadoId) {
                $loteAttributes['dispositivo_invitado_id'] = $dispositivoInvitadoId;
            }

            if (!empty($datos['coordenadas']) && is_array($datos['coordenadas'])) {
                $loteAttributes['area'] = DB::raw($this->convertirCoordenadasAPoligono($datos['coordenadas']));
            }

            $lote = Lote::create($loteAttributes);

            foreach ($datos['proyectos'] as $proyectoData) {
                $proyectoDataInsert = [
                    'uuid_movil'     => $proyectoData['uuid_movil'],
                    'lote_id'        => $lote->id,
                    'titulo'         => $proyectoData['titulo'],
                    'descripcion'    => $proyectoData['descripcion'] ?? null,
                    'tipo_ensayo'    => $proyectoData['tipo_ensayo'] ?? null,
                    'variedad'       => $proyectoData['variedad'],
                    'fecha_siembra'  => $proyectoData['fecha_siembra'] ?? null,
                    'tipo_acolchado' => $proyectoData['tipo_acolchado'] ?? null,
                ];

                // Asignar responsable según el tipo de usuario
                if ($dispositivoInvitadoId) {
                    $proyectoDataInsert['dispositivo_invitado_id'] = $dispositivoInvitadoId;
                } else {
                    $proyectoDataInsert['responsable_id'] = $responsableId;
                }

                $proyecto = Proyecto::create($proyectoDataInsert);

                if (!empty($proyectoData['colaboradores'])) {
                    $proyecto->colaboradores()->sync($proyectoData['colaboradores']);
                }
            }

            return $lote;
        });
    }
}
