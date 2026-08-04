<?php

namespace Modules\TalentoHumano\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\TalentoHumano\Entities\ThVehicle;

class ThVehicleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $vehicles = [
            ['placa' => 'PEI-7749', 'model' => 'SZ GRAND VITARA 2.5'], //
            ['placa' => 'PEI-4486', 'model' => 'SZ GRAND VITARA 2.5'], //
            ['placa' => 'PEI-1579', 'model' => 'SZ GRAND VITARA 2.7'], //
            ['placa' => 'PEQ-0700', 'model' => 'SZ GRAND VITARA 2.0'], //
            ['placa' => 'PEQ-0706', 'model' => 'SZ GRAND VITARA 2.0'], //
            ['placa' => 'PEQ-0715', 'model' => 'GRAND VITARA 1.6'], //
            ['placa' => 'PEQ-0702', 'model' => 'GRAND VITARA 1.6'], //
            ['placa' => 'PEQ-0711', 'model' => 'GRAND VITARA 1.6'], //
            ['placa' => 'PEQ-0721', 'model' => 'GRAND VITARA 1.6'], //
            ['placa' => 'PEQ-0373', 'model' => 'HYNDAI COUNTY'], //
            ['placa' => 'QEA-0011', 'model' => 'TOYOTA HIACE'], //
        ];

        foreach ($vehicles as $vehicle) {
            ThVehicle::updateOrCreate(
                ['placa' => $vehicle['placa']],
                ['model' => $vehicle['model'], 'is_active' => true]
            );
        }

        $this->command->info('Catálogo de vehículos cargado exitosamente.');
    }
}
