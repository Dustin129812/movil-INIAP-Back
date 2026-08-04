<?php

namespace Modules\Investigacion\Database\Seeders;

use App\Models\FeatureFlag;
use Illuminate\Database\Seeder;

class FeatureFlagSeeder extends Seeder
{
    public function run(): void
    {
        $flags = [
            [
                'name' => 'dashboard',
            ],
            [
                'name' => 'plan_operativo_anual',
            ],
            [
                'name' => 'planeacion_semanal',
            ],
            [
                'name' => 'conocimiento',
            ]
        ];

        foreach ($flags as $flag) {
            FeatureFlag::firstOrCreate(
                ['name' => $flag['name']],
                [
                    'is_active' => true
                ]
            );
        }
    }
}
