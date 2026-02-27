<?php

namespace Modules\Produccion\Services;

use Modules\Produccion\Entities\Lote;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class LoteService
{
    public function crearLote(array $datos): Lote
    {
        return DB::transaction(function () use ($datos) {

            $datos['codigo'] = $this->generarCodigoLote($datos['location_id']);
            $datos['poligono'] = DB::raw("ST_GeomFromGeoJSON('" . $datos['poligono_geojson'] . "')");

            return Lote::create($datos);
        });
    }

    /**
     * Genera un código automático secuencial: L-{LOCATION_ID}-{AÑO}-{SECUENCIAL}
     */
    /**
     * Genera un código automático secuencial a prueba de eliminaciones.
     * Ejemplo: L-001-2026-0005
     */
    private function generarCodigoLote(int $locationId): string
    {
        $anio = Carbon::now()->year;
        $prefijo = sprintf("L-%03d-%s-", $locationId, $anio);

        // Buscamos el lote que tenga el código más alto con ese prefijo
        $ultimoLote = Lote::where('codigo', 'LIKE', $prefijo . '%')
            ->orderBy('codigo', 'desc')
            ->first();

        if ($ultimoLote) {
            // Extraemos los últimos 4 dígitos (ej: "0006" -> 6) y le sumamos 1
            $consecutivo = (int) substr($ultimoLote->codigo, -4);
            $nuevoSecuencial = $consecutivo + 1;
        } else {
            // Si es el primer lote del año en esa locación
            $nuevoSecuencial = 1;
        }

        return $prefijo . str_pad($nuevoSecuencial, 4, '0', STR_PAD_LEFT);
    }

    public function segmentarLote(int $parentId, array $datos): Lote
    {
        return DB::transaction(function () use ($parentId, $datos) {
            $padre = Lote::findOrFail($parentId);

            $estaContenido = DB::selectOne("
            SELECT ST_Contains(
                poligono::geometry,
                ST_GeomFromGeoJSON(?)::geometry
            ) as valid
            FROM produccion.lotes
            WHERE id = ?",
                [$datos['poligono_geojson'], $parentId]
            )->valid;

            if (!$estaContenido) {
                throw new \Exception("Error Geográfico: La parcela dibujada se sale de los límites del Lote Padre.");
            }

            $superficieOcupada = Lote::where('parent_id', $parentId)->sum('superficie_hectareas');
            if (($superficieOcupada + $datos['superficie_hectareas']) > $padre->superficie_hectareas) {
                throw new \Exception("Error de Capacidad: No hay suficiente superficie disponible en el lote principal.");
            }

            $datos['parent_id'] = $parentId;
            $datos['estado'] = $datos['estado'] ?? 'PREPARACION';
            $datos['location_id'] = $padre->location_id;
            $datos['codigo'] = $this->generarCodigoLote($padre->location_id);
            $datos['poligono'] = DB::raw("ST_GeomFromGeoJSON('" . $datos['poligono_geojson'] . "')");

            return Lote::create($datos);
        });
    }

    public function actualizarLote(int $id, array $datos): Lote
    {
        return DB::transaction(function () use ($id, $datos) {
            $lote = Lote::findOrFail($id);

            // 1. Actualización de datos básicos
            if (isset($datos['nombre'])) $lote->nombre = $datos['nombre'];
            if (isset($datos['estado'])) $lote->estado = $datos['estado'];

            // 2. Si hay redibujo espacial, aplicamos validaciones geográficas
            if (!empty($datos['poligono_geojson'])) {

                // Si es una parcela (tiene padre), verificamos que el nuevo dibujo no se salga
                if ($lote->parent_id) {
                    $padre = Lote::find($lote->parent_id);
                    $estaContenido = DB::selectOne("
                        SELECT ST_Contains(
                            poligono::geometry,
                            ST_GeomFromGeoJSON(?)::geometry
                        ) as valid
                        FROM produccion.lotes
                        WHERE id = ?",
                        [$datos['poligono_geojson'], $padre->id]
                    )->valid;

                    if (!$estaContenido) {
                        throw new \Exception("Error Geográfico: El nuevo dibujo de la parcela se sale de los límites del Lote Padre.");
                    }

                    // Validar suma de áreas excluyendo el área actual del lote
                    $superficieOtros = Lote::where('parent_id', $lote->parent_id)
                        ->where('id', '!=', $lote->id)
                        ->sum('superficie_hectareas');

                    if (($superficieOtros + $datos['superficie_hectareas']) > $padre->superficie_hectareas) {
                        throw new \Exception("Error de Capacidad: La nueva superficie excede el área disponible del lote principal.");
                    }
                }

                $lote->superficie_hectareas = $datos['superficie_hectareas'];
                $lote->poligono = DB::raw("ST_GeomFromGeoJSON('" . $datos['poligono_geojson'] . "')");
            }

            $lote->save();
            return $lote;
        });
    }

    public function eliminarLote(int $id): void
    {
        $lote = Lote::findOrFail($id);

        if ($lote->hijos()->count() > 0) {
            throw new \Exception("Integridad Referencial: No puedes eliminar un Lote Maestro que contiene parcelas. Elimina las subdivisiones primero.");
        }

        $lote->delete();
    }
}
