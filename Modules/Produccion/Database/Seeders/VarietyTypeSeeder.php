<?php

namespace Modules\Produccion\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class VarietyTypeSeeder extends Seeder
{
    public function run()
    {
        $types = [
            ['name' => 'SEMILLA'],              // Para sacos certificada, básica, etc.
            ['name' => 'MATERIAL VEGETATIVO'],  // Para plantas, esquejes, varetas
            ['name' => 'MATERIA PRIMA'],        // Para fruta, grano comercial, consumo
            ['name' => 'INSUMO DE INVESTIGACIÓN'] // Por si acaso
        ];

        foreach ($types as $type) {
            DB::table('variety_types')->updateOrInsert(
                ['name' => $type['name']], // Busca por nombre
                ['created_at' => now(), 'updated_at' => now()]
            );
        }
    }
}
