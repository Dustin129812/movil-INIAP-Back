<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\LogisticSupport;

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
