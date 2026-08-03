<?php

namespace Modules\TalentoHumano\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\TalentoHumano\Entities\ThHoliday;

class ThHolidaySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Feriados nacionales de Ecuador para 2025
        $holidays = [
            ['date' => '2025-01-01', 'name' => 'Año Nuevo'],
            ['date' => '2025-03-03', 'name' => 'Carnaval'],
            ['date' => '2025-03-04', 'name' => 'Carnaval'],
            ['date' => '2025-04-18', 'name' => 'Viernes Santo'],
            ['date' => '2025-05-01', 'name' => 'Día del Trabajo'],
            ['date' => '2025-05-23', 'name' => 'Traslado Batalla de Pichincha'],
            ['date' => '2025-08-08', 'name' => 'Traslado Primer Grito de Independencia'],
            ['date' => '2025-10-10', 'name' => 'Traslado Independencia de Guayaquil'], // Relevante para tus PDFs [cite: 10]
            ['date' => '2025-11-03', 'name' => 'Día de Difuntos e Independencia de Cuenca'],
            ['date' => '2025-12-25', 'name' => 'Navidad'],
            ['date' => '2025-12-26', 'name' => 'Traslado Navidad (Puente)'],
        ];

        foreach ($holidays as $holiday) {
            ThHoliday::updateOrCreate(
                ['date' => $holiday['date']],
                ['name' => $holiday['name']]
            );
        }
        $this->command->info('Catálogo de feriados 2025 cargado exitosamente.');
    }
}
