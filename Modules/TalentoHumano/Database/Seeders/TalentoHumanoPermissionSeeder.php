<?php

namespace Modules\TalentoHumano\Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class TalentoHumanoPermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        $guards = ['web', 'api'];

        $permissions = [
            'th.view.module',
            'th.horas_extras.registrar',
            'th.horas_extras.revisar',
            'th.horas_extras.aprobar_daf',
            'th.reportes.ver',
            'th.alertas.ver',
            'th.configuracion.gestionar'
        ];

        foreach ($permissions as $permission) {
            foreach ($guards as $guard) {
                Permission::findOrCreate($permission, $guard);
            }
        }

        foreach ($guards as $guard) {
            // Rol: Conductor
            $conductor = Role::findOrCreate('TH Conductor', $guard);
            $conductor->givePermissionTo([
                'th.view.module',
                'th.horas_extras.registrar'
            ]);

            // Rol: Supervisor (Jefe Inmediato)
            $supervisor = Role::findOrCreate('TH Jefe Inmediato', $guard);
            $supervisor->givePermissionTo([
                'th.view.module',
                'th.horas_extras.revisar'
            ]);

            // Rol: DAF
            $daf = Role::findOrCreate('TH DAF', $guard);
            $daf->givePermissionTo([
                'th.view.module',
                'th.horas_extras.aprobar_daf'
            ]);

            // Rol: Admin DATH
            $admin = Role::findOrCreate('TH DTH', $guard);
            $admin->givePermissionTo([
                'th.view.module',
                'th.reportes.ver',
                'th.alertas.ver',
                'th.configuracion.gestionar'
            ]);

            if ($superAdmin = Role::where('name', 'Super-Admin')->where('guard_name', $guard)->first()) {
                $superAdmin->givePermissionTo($permissions);
            }
        }
    }
}
