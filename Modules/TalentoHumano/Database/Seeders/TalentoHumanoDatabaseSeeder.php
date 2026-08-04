<?php

namespace Modules\TalentoHumano\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Database\Eloquent\Model;

class TalentoHumanoDatabaseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        Model::unguard();
        $this->call([
            TalentoHumanoPermissionSeeder::class,
            ThVehicleSeeder::class,
            ThActivityTypeSeeder::class,
            ThHolidaySeeder::class
        ]);
    }
}
