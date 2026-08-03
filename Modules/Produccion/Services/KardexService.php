<?php

namespace Modules\Produccion\Services;

use Modules\Produccion\Entities\Kardex;
use Illuminate\Support\Facades\DB;
use Exception;
use Modules\Produccion\Entities\Lote;

class KardexService
{
    /**
     * Registra la compra o entrada de un insumo a la bodega.
     */
    public function registrarIngreso(int $bodegaId, int $insumoId, float $cantidad, float $costoUnitario, string $referencia = null): Kardex
    {
        return DB::transaction(function () use ($bodegaId, $insumoId, $cantidad, $costoUnitario, $referencia) {

            $ultimoMovimiento = $this->obtenerUltimoMovimiento($bodegaId, $insumoId);

            $saldoAnteriorCantidad = $ultimoMovimiento ? $ultimoMovimiento->saldo_cantidad : 0;
            $costoPromedioAnterior = $ultimoMovimiento ? $ultimoMovimiento->costo_promedio : 0;

            // Fórmulas de Costo Promedio Ponderado (PMP)
            $nuevoSaldoCantidad = $saldoAnteriorCantidad + $cantidad;
            $valorInventarioAnterior = $saldoAnteriorCantidad * $costoPromedioAnterior;
            $valorNuevoIngreso = $cantidad * $costoUnitario;

            $nuevoCostoPromedio = ($valorInventarioAnterior + $valorNuevoIngreso) / $nuevoSaldoCantidad;

            return Kardex::create([
                'bodega_id'            => $bodegaId,
                'insumo_id'            => $insumoId,
                'tipo_movimiento'      => 'INGRESO',
                'cantidad'             => $cantidad,
                'costo_unitario'       => $costoUnitario,
                'costo_total'          => $valorNuevoIngreso,
                'saldo_cantidad'       => $nuevoSaldoCantidad,
                'costo_promedio'       => $nuevoCostoPromedio,
                'documento_referencia' => $referencia,
            ]);
        });
    }

    /**
     * Registra el uso de un insumo en el campo (Descuenta del inventario).
     */
    public function registrarEgreso(int $bodegaId, int $insumoId, float $cantidadDeseada, string $referencia = null): Kardex
    {
        return DB::transaction(function () use ($bodegaId, $insumoId, $cantidadDeseada, $referencia) {

            $ultimoMovimiento = $this->obtenerUltimoMovimiento($bodegaId, $insumoId);

            if (!$ultimoMovimiento || $ultimoMovimiento->saldo_cantidad < $cantidadDeseada) {
                throw new Exception("Stock insuficiente en la bodega para el insumo solicitado.");
            }

            // En un egreso, el insumo sale costeado al precio promedio actual. El PMP no cambia en salidas.
            $costoPromedioActual = $ultimoMovimiento->costo_promedio;
            $nuevoSaldoCantidad = $ultimoMovimiento->saldo_cantidad - $cantidadDeseada;
            $costoTotalEgreso = $cantidadDeseada * $costoPromedioActual;

            return Kardex::create([
                'bodega_id'            => $bodegaId,
                'insumo_id'            => $insumoId,
                'tipo_movimiento'      => 'EGRESO',
                'cantidad'             => $cantidadDeseada,
                'costo_unitario'       => $costoPromedioActual,
                'costo_total'          => $costoTotalEgreso,
                'saldo_cantidad'       => $nuevoSaldoCantidad,
                'costo_promedio'       => $costoPromedioActual,
                'documento_referencia' => $referencia,
            ]);
        });
    }

    /**
     * Obtiene la "fotografía" más reciente del estado de un insumo en una bodega.
     */
    private function obtenerUltimoMovimiento(int $bodegaId, int $insumoId): ?Kardex
    {
        return Kardex::where('bodega_id', $bodegaId)
            ->where('insumo_id', $insumoId)
            ->orderBy('id', 'desc')
            ->first();
    }

    // Modules/Produccion/Services/LoteService.php

    public function segmentarLote(int $parentId, array $datosHijo): Lote
    {
        return DB::transaction(function () use ($parentId, $datosHijo) {
            $padre = Lote::findOrFail($parentId);

            $superficieOcupada = Lote::where('parent_id', $parentId)->sum('superficie_hectareas');
            if (($superficieOcupada + $datosHijo['superficie_hectareas']) > $padre->superficie_hectareas) {
                throw new \Exception("La superficie del segmento excede el área disponible del lote padre.");
            }

            return Lote::create([
                'parent_id' => $parentId,
                'location_id' => $padre->location_id,
                'nombre' => $datosHijo['nombre'],
                'codigo' => $this->generarCodigoSubLote($padre),
                'superficie_hectareas' => $datosHijo['superficie_hectareas'],
                'poligono' => DB::raw("ST_GeomFromGeoJSON('" . $datosHijo['poligono_geojson'] . "')"),
                'estado' => 'PREPARACION'
            ]);
        });
    }
}
