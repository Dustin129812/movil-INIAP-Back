<?php

namespace Modules\Administracion\Services;

use Modules\Administracion\Entities\Dispatch;
use Modules\Administracion\Entities\Vehicle;

class FleetAnalyticsService
{
    public function getAnalyticsData(?string $startDate = null, ?string $endDate = null): array
    {
        $vehicles = Vehicle::all();

        $dispatchesQuery = Dispatch::with(['weekActivity.user', 'weekActivity.materials'])
            ->whereNotNull('vehicle_id');

        if ($startDate && $endDate) {
            $dispatchesQuery->whereHas('weekActivity', function ($query) use ($startDate, $endDate) {
                $query->whereBetween('date', [$startDate, $endDate]);
            })->with(['weekActivity' => function ($query) use ($startDate, $endDate) {
                $query->whereBetween('date', [$startDate, $endDate]);
            }]);
        }

        return [
            'vehicles' => $vehicles,
            'dispatches' => $dispatchesQuery->get(),
        ];
    }
}
