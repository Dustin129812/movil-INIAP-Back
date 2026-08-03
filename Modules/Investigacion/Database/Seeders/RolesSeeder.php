<?php

namespace Modules\Investigacion\Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission; // Importante: Importar el modelo Permission
use Spatie\Permission\PermissionRegistrar;

class RolesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        Role::firstOrCreate(['name' => 'user', 'guard_name' => 'api']);
        Role::firstOrCreate(['name' => 'station-director', 'guard_name' => 'api']);
        Role::firstOrCreate(['name' => 'research-direction', 'guard_name' => 'api']);
        Role::firstOrCreate(['name' => 'product-manager', 'guard_name' => 'api']);
        Role::firstOrCreate(['name' => 'researcher', 'guard_name' => 'api']);

        $adminRole = Role::firstOrCreate(['name' => 'administrador', 'guard_name' => 'api']);

        $permissions = [
            'view-admin-panel',
            'manage-patch-notes',
            'manage-permissions',
            'manage-roles',
            'manage-support-tickets',
            'manage-users'
        ];

        foreach ($permissions as $permissionName) {
            $permission = Permission::firstOrCreate(['name' => $permissionName, 'guard_name' => 'api']);

            if (!$adminRole->hasPermissionTo($permission)) {
                $adminRole->givePermissionTo($permission);
            }
        }
    }
}
