<?php

namespace Modules\Investigacion\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Database\Eloquent\Model;

class InvestigacionDatabaseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $this->call(MiscelaneoSeeder::class);
        $this->call(PositionSeeder::class);
        $this->call(RolesSeeder::class);
        $this->call(FeatureFlagSeeder::class);
        $this->call(ExpenseTypeSeeder::class);
        $this->call(DocumentTypeSeeder::class);
    }
}
