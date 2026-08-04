<?php

namespace Modules\Investigacion\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Investigacion\Entities\ResearchArea;
use Modules\Investigacion\Entities\ResearchLine;

class ResearchTaxonomySeeder extends Seeder
{
    public function run(): void
    {
        $areas = [
            'Incremento de la productividad' => [
                'Mejoramiento genético',
                'Manejo integrado de cultivos',
                'Manejo integrado de plagas y enfermedades',
            ],
            'Incorporación de valor agregado' => [
                'Agroindustria',
                'Calidad e inocuidad',
                'Nutrición',
            ],
            'Manejo y conservación de los recursos naturales' => [
                'Suelos y Aguas',
                'Recursos Fitogenéticos',
                'Forestería',
                'Agroecología',
            ],
            'Otros' => [
                'Economía agrícola',
                'Transferencia de tecnología',
            ]
        ];

        foreach ($areas as $areaName => $lines) {
            // firstOrCreate evita duplicados si corres el seeder dos veces
            $area = ResearchArea::firstOrCreate(['name' => $areaName]);

            foreach ($lines as $lineName) {
                ResearchLine::firstOrCreate([
                    'research_area_id' => $area->id,
                    'name' => $lineName
                ]);
            }
        }
    }
}
