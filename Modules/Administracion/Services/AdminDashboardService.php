<?php

namespace Modules\Administracion\Services;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;
use Spatie\Activitylog\Models\Activity;

class AdminDashboardService
{
    public function getMetrics(): array
    {
        $usuariosActivos = User::count();
        $rolesSistema    = Role::count();

        $actividadReciente = Activity::with('causer')
            ->latest()
            ->take(6)
            ->get()
            ->map(function ($log) {
                return [
                    'id'     => $log->id,
                    'user'   => $log->causer ? $log->causer->name : 'Sistema SIMPAGI',
                    'action' => $log->description,
                    'time'   => $log->created_at->diffForHumans(),
                    'type'   => $this->determinarTipoLog($log->log_name ?? 'default')
                ];
            });

        return [
            'usuarios_activos'   => $usuariosActivos,
            'roles_sistema'      => $rolesSistema,
            'consultas_bd'       => $this->getPgConnections(),
            'salud_server'       => 'Óptima',
            'actividad_reciente' => $actividadReciente,
        ];
    }

    /**
     * Mapea el 'log_name' de Spatie a la categoría visual de iconos en Next.js
     */
    private function determinarTipoLog(string $logName): string
    {
        return match($logName) {
            'security', 'roles', 'permissions' => 'security',
            'users', 'auth' => 'user',
            'alert', 'errors' => 'alert',
            default => 'system',
        };
    }

    /**
     * Consulta nativa optimizada para PostgreSQL que devuelve conexiones activas
     */
    private function getPgConnections(): int
    {
        try {
            $query = DB::select("SELECT count(*) as total FROM pg_stat_activity WHERE state = 'active'");
            return $query[0]->total ?? 0;
        } catch (\Exception $e) {
            return 0;
        }
    }
}
