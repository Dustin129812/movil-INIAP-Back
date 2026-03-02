<?php

namespace Modules\Produccion\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Database\Eloquent\Model;
use Modules\Produccion\Entities\ActividadMaquinaria;
use Modules\Produccion\Entities\Bodega;
use Modules\Produccion\Entities\UnidadMedida;
use Modules\Produccion\Entities\Insumo;
use Modules\Produccion\Entities\Lote;
use Modules\Produccion\Entities\LibroCampo;
use Modules\Produccion\Entities\Maquinaria;
use Modules\Produccion\Services\KardexService;
use Modules\Produccion\Services\ProduccionService;

class ProduccionDatabaseSeeder extends Seeder
{
    public function run()
    {
        Model::unguard();

        // 1. Catálogos Base
        $bodega = Bodega::create(['location_id' => 1, 'nombre' => 'Bodega Central Santa Catalina', 'descripcion' => 'Principal']);
        $unidad = UnidadMedida::create(['nombre' => 'Kilogramo', 'abreviatura' => 'KG']);
        $insumo = Insumo::create(['unidad_medida_id' => $unidad->id, 'tipo' => 'FERTILIZANTE', 'nombre' => 'Urea 46% N']);
        $maquinaria = Maquinaria::create(['nombre' => 'Tractor John Deere 5075E', 'costo_hora' => 15.50]);

        // 2. Comprar Insumos (Usamos tu motor financiero)
        $kardexService = app(KardexService::class);
        $kardexService->registrarIngreso($bodega->id, $insumo->id, 20, 60.00, 'Compra Inicial Seeder'); // 20 sacos a $60 = $1200

        // 3. Crear Lote y Libro de Campo
        // 3. Crear Estructura Jerárquica de Lotes
        $lotePadre = Lote::create([
            'location_id' => 1,
            'codigo' => 'L-CAT-2026-MAIN',
            'nombre' => 'Estación Experimental Santa Catalina - Sector A',
            'superficie_hectareas' => 65.0,
            'estado' => 'PRODUCCION'
        ]);

        // Sub-lote / Segmento (La parcela donde realmente se siembra)
        $subLote = Lote::create([
            'parent_id' => $lotePadre->id, // Vinculamos al padre
            'location_id' => 1,
            'codigo' => 'L-001-2026-0001',
            'nombre' => 'Parcela Experimental Maíz KOPIA',
            'superficie_hectareas' => 2.5,
            'estado' => 'PREPARACION'
        ]);

        // El Libro de Campo ahora se vincula al Sub-lote
        $libro = LibroCampo::create([
            'lote_id' => $subLote->id,
            'codigo' => 'LC-2026-0001',
            'nombre' => 'Ensayo Nacional de Rendimiento - Maíz 2026',
            'fecha_inicio' => '2026-02-25',
            'estado' => 'ABIERTO'
        ]);

        // 4. Registrar Actividades (Usamos tu motor de Producción)
        $produccionService = app(ProduccionService::class);

        // A. Insumo
        $produccionService->registrarLaborConInsumo([
            'libro_id' => $libro->id, 'bodega_id' => $bodega->id, 'insumo_id' => $insumo->id,
            'fecha' => '2026-02-25', 'labor' => 'Fertilización Inicial', 'cantidad' => 2.5, 'observaciones' => 'Seeder'
        ]);

        // B. Personal (Asumiendo que el usuario ID 5 existe, si no, pon el 1)
        $produccionService->registrarTrabajoPersonal([
            'libro_id' => $libro->id, 'user_id' => 5,
            'fecha' => '2026-02-25', 'labor' => 'Control de malezas', 'horas_trabajadas' => 4, 'costo_hora' => 3.50
        ]);

        // C. Maquinaria (Registro directo manual, o puedes moverlo al ProduccionService luego)
        ActividadMaquinaria::create([
            'libro_campo_id' => $libro->id, 'maquinaria_id' => $maquinaria->id,
            'fecha' => '2026-02-25', 'horas_uso' => 3.5, 'costo_total' => 3.5 * 15.50
        ]);
    }
}
