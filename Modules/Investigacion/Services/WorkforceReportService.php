<?php

namespace Modules\Investigacion\Services;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Modules\Investigacion\Entities\WeekActivity;

class WorkforceReportService
{
    public function getHoardingRanking(string $startDate, string $endDate, int $locationId): Collection
    {
        $activityModel = new WeekActivity();
        $activityTable = $activityModel->getTable();

        // 1. Ranking base: Contamos "Días-Hombre" reales agrupando Obrero+Fecha
        $technicians = DB::table($activityTable . ' as wa')
            ->join('week_activity_logistic_support_user as pivot', 'wa.id', '=', 'pivot.weekly_activity_id')
            ->join('users as technicians', 'wa.user_id', '=', 'technicians.id')
            ->where('technicians.location_id', $locationId)
            ->whereBetween('wa.date', [$startDate, $endDate])
            ->whereIn('pivot.status', ['accepted', 'pending'])
            ->select(
                'technicians.id as technician_id',
                'technicians.name as technician_name',
                DB::raw('COUNT(DISTINCT pivot.user_id) as unique_workers_requested'),
                // CORRECCIÓN MAGISTRAL: Concatenamos ID y Fecha. Así 5 tareas del mismo obrero en 1 día = 1 Jornada.
                DB::raw("COUNT(DISTINCT pivot.user_id || '-' || wa.date) as total_support_days_requested"),
                DB::raw('ROUND(AVG(wa.percentage), 2) as average_activity_compliance')
            )
            ->groupBy('technicians.id', 'technicians.name')
            ->orderByDesc('total_support_days_requested')
            ->get();

        // 2. Frecuencia de uso: También corregimos para contar días reales por obrero
        $workerFrequencies = DB::table($activityTable . ' as wa')
            ->join('week_activity_logistic_support_user as pivot', 'wa.id', '=', 'pivot.weekly_activity_id')
            ->join('users as workers', 'pivot.user_id', '=', 'workers.id')
            ->whereBetween('wa.date', [$startDate, $endDate])
            ->whereIn('pivot.status', ['accepted', 'pending'])
            ->select(
                'wa.user_id as technician_id',
                'workers.name as worker_name',
                // CORRECCIÓN AQUÍ TAMBIÉN
                DB::raw("COUNT(DISTINCT pivot.user_id || '-' || wa.date) as usage_count")
            )
            ->groupBy('wa.user_id', 'workers.name')
            ->orderByDesc('usage_count')
            ->get()
            ->groupBy('technician_id');

        // 3. Inyección de métricas
        return $technicians->map(function ($tech) use ($workerFrequencies) {
            $techWorkers = $workerFrequencies->get($tech->technician_id) ?? collect([]);
            $mostRepeated = $techWorkers->first();

            $tech->most_requested_worker_name = $mostRepeated ? $mostRepeated->worker_name : 'Ninguno';
            $tech->most_requested_worker_count = $mostRepeated ? $mostRepeated->usage_count : 0;

            $tech->workers_breakdown = $techWorkers->map(fn($w) => [
                'nombre' => $w->worker_name,
                'usos' => (int) $w->usage_count
            ])->all();

            return $tech;
        });
    }
}
