<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;

class RolesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Role::firstOrCreate(['name' => 'user', 'guard_name' => 'api']);
        Role::firstOrCreate(['name' => 'product-manager', 'guard_name' => 'api']);
        Role::firstOrCreate(['name' => 'station-director', 'guard_name' => 'api']);
        Role::firstOrCreate(['name' => 'research-direction', 'guard_name' => 'api']);
        Role::firstOrCreate(['name' => 'product-manager', 'guard_name' => 'api']);
        Role::firstOrCreate(['name' => 'researcher', 'guard_name' => 'api']);
    }
}
