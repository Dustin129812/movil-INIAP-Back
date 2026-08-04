<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Investigacion\Database\Seeders\DocumentTypeSeeder;
use Modules\Investigacion\Database\Seeders\ExpenseTypeSeeder;
use Modules\Investigacion\Database\Seeders\FeatureFlagSeeder;
use Modules\Investigacion\Database\Seeders\logisticSeeder;
use Modules\Investigacion\Database\Seeders\MiscelaneoSeeder;
use Modules\Investigacion\Database\Seeders\PositionSeeder;
use Modules\Investigacion\Database\Seeders\RolesSeeder;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call(MiscelaneoSeeder::class);
        $this->call(PositionSeeder::class);
        $this->call(RolesSeeder::class);
        $this->call(FeatureFlagSeeder::class);
        $this->call(ExpenseTypeSeeder::class);
        $this->call(DocumentTypeSeeder::class);
    }
}
