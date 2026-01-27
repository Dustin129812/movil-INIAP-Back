<?php

namespace Modules\Produccion\Database\Seeders;

use Illuminate\Database\Seeder;

class ProduccionDatabaseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $this->call(VarietyTypeSeeder::class);
    }
}
