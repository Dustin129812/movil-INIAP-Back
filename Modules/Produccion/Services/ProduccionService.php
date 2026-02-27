<?php

namespace Modules\Produccion\Services;

use Modules\Produccion\Entities\Actividad;
use Modules\Produccion\Entities\ActividadMaquinaria;
use Modules\Produccion\Entities\ActividadPersonal;
use Modules\Produccion\Entities\LibroCampo;
use Illuminate\Support\Facades\DB;
use Modules\Produccion\Entities\Maquinaria;

class ProduccionService {
    public function __construct(protected KardexService $kardexService) {}

    public function registrarLaborConInsumo(array $data) {
        return DB::transaction(function () use ($data) {
            // 1. Ejecutar el egreso en el inventario
            $movimiento = $this->kardexService->registrarEgreso(
                $data['bodega_id'],
                $data['insumo_id'],
                $data['cantidad'],
                "Labor: {$data['labor']} en Libro: {$data['libro_id']}"
            );

            // 2. Crear la actividad vinculada al costo real del Kardex
            return Actividad::create([
                'libro_campo_id'  => $data['libro_id'],
                'kardex_id'       => $movimiento->id,
                'fecha'           => $data['fecha'],
                'labor'           => $data['labor'],
                'cantidad_insumo' => $data['cantidad'],
                'costo_actividad' => $movimiento->costo_total, // Aquí capturamos el valor financiero
                'observaciones'   => $data['observaciones'] ?? null
            ]);
        });
    }

    public function registrarTrabajoPersonal(array $data): ActividadPersonal
    {
        return DB::transaction(function () use ($data) {
            return ActividadPersonal::create([
                'libro_campo_id'   => $data['libro_id'],
                'user_id'          => $data['user_id'],
                'fecha'            => $data['fecha'],
                'labor'            => $data['labor'],
                'horas_trabajadas' => $data['horas_trabajadas'],
                'costo_hora'       => $data['costo_hora'],
                'costo_total'      => $data['horas_trabajadas'] * $data['costo_hora']
            ]);
        });
    }

    public function registrarCosechaYCerrar(LibroCampo $libro, array $data)
    {
        return DB::transaction(function () use ($libro, $data) {

            $costoInsumos = $libro->actividades()->sum('costo_actividad');
            $costoPersonal = $libro->actividadesPersonal()->sum('costo_total');
            $costoMaquinaria = $libro->actividadesMaquinaria()->sum('costo_total');
            $costoTotalInversion = $costoInsumos + $costoPersonal + $costoMaquinaria;

            $costoUnitario = $costoTotalInversion / $data['cantidad_cosechada'];

            $movimientoKardex = $this->kardexService->registrarIngreso(
                $data['bodega_id'],
                $data['insumo_cosechado_id'],
                $data['cantidad_cosechada'],
                $costoUnitario,
                "Cosecha del Libro: {$libro->codigo}"
            );

            $libro->update([
                'estado'              => 'CERRADO',
                'fecha_fin'           => $data['fecha_cosecha'],
                'cantidad_cosechada'  => $data['cantidad_cosechada'],
                'insumo_cosechado_id' => $data['insumo_cosechado_id'],
                'kardex_ingreso_id'   => $movimientoKardex->id
            ]);

            return [
                'libro' => $libro,
                'kardex' => $movimientoKardex,
                'resumen_financiero' => [
                    'inversion_total' => round($costoTotalInversion, 2),
                    'cantidad_obtenida' => $data['cantidad_cosechada'],
                    'costo_por_unidad' => round($costoUnitario, 2)
                ]
            ];
        });
    }

    public function crearLibroCampo(array $data): LibroCampo
    {
        return DB::transaction(function () use ($data) {
            $data['codigo'] = 'LC-' . date('Y') . '-' . str_pad(LibroCampo::count() + 1, 4, '0', STR_PAD_LEFT);
            $data['estado'] = 'ABIERTO';

            return LibroCampo::create($data);
        });
    }

    public function registrarUsoMaquinaria(array $data): ActividadMaquinaria
    {
        return DB::transaction(function () use ($data) {
            $maquina = Maquinaria::findOrFail($data['maquinaria_id']);

            return ActividadMaquinaria::create([
                'libro_campo_id' => $data['libro_id'],
                'maquinaria_id'  => $data['maquinaria_id'],
                'fecha'          => $data['fecha'],
                'horas_uso'      => $data['horas_uso'],
                'costo_total'    => $data['horas_uso'] * $maquina->costo_hora,
            ]);
        });
    }
}
