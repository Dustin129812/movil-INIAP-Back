<?php

namespace Modules\TalentoHumano\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\TalentoHumano\Entities\ThActivityType;

class ThActivityTypeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $activities = [
            'Movilización', // [cite: 10]
            'Recorrido', // [cite: 30]
            'Comisión', // [cite: 30]
            'Traslado de Personal', // Basado en "Traslado personal INIAP D.E." [cite: 10]
            'Traslado Aeropuerto', // Basado en "TABABELA" [cite: 10]
            'Otro', // Para cualquier otro caso
        ];

        foreach ($activities as $activityName) {
            ThActivityType::updateOrCreate(
                ['name' => $activityName],
                ['is_active' => true]
            );
        }
        $this->command->info('Catálogo de tipos de actividad cargado exitosamente.');
    }
}
