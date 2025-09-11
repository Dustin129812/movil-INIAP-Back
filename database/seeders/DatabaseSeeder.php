<?php

namespace Database\Seeders;

use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

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
        $this->call(logisticSeeder::class);
        $this->call(FeatureFlagSeeder::class);
        $this->call(ExpenseTypeSeeder::class);
    }
}
