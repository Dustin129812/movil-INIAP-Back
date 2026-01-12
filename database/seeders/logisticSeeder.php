<?php

namespace Database\Seeders;

use App\Modules\Planificacion\Models\LogisticSupport;
use Illuminate\Database\Seeder;

class logisticSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $logistics = [
            ['name' => 'Personal'],
            ['name' => 'Personal DI'],
            ['name' => 'MH'],
            ['name' => 'JM'],
            ['name' => 'PG'],
            ['name' => 'MA'],
            ['name' => 'CP'],
            ['name' => 'TC'],
            ['name' => 'EL'],
            ['name' => 'Personal GET y otros proyectos'],
            ['name' => 'Personal administrativo y técnico GET'],
            ['name' => 'Personal administrativo y técnico GET. Personal de huertos'],
        ];

        foreach ($logistics as $logistic) {
            LogisticSupport::create($logistic);
        }
    }
}
