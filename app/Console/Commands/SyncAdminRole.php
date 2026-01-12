<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class SyncAdminRole extends Command
{
    protected $signature = 'app:sync-admin-role';
    protected $description = 'Asegura que el rol "administrador" tenga todos los permisos necesarios.';

    public function handle()
    {
        $this->info('Buscando el rol "administrador"...');

        // Buscamos el rol. Si no existe, lo creamos.
        $adminRole = Role::firstOrCreate(['name' => 'administrador', 'guard_name' => 'api']);

        if ($adminRole) {
            $this->info('Rol "administrador" encontrado/creado. Sincronizando permisos...');
        } else {
            $this->error('No se pudo encontrar o crear el rol "administrador".');
            return 1;
        }

        // Lista de todos los permisos que un administrador DEBE tener.
        $adminPermissions = [
            'view-admin-panel',
            'manage-users',
            'manage-roles',
            'manage-permissions',
            'manage-support-tickets',
            'view-station-needs-report',
            'manage-patch-notes',
        ];

        // Creamos cada permiso si no existe y lo guardamos en un array.
        $permissionsToSync = [];
        foreach ($adminPermissions as $permissionName) {
            $permission = Permission::firstOrCreate(['name' => $permissionName, 'guard_name' => 'api']);
            $permissionsToSync[] = $permission;
            $this->line("Permiso '{$permissionName}' asegurado.");
        }

        // Sincronizamos: El rol 'administrador' tendrá EXACTAMENTE estos permisos.
        $adminRole->syncPermissions($permissionsToSync);

        $this->info("\n¡Éxito! El rol 'administrador' ha sido sincronizado con todos los permisos necesarios.");
        return 0;
    }
}
