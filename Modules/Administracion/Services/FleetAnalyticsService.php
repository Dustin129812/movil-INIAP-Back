<?php

namespace Modules\Administracion\Services;

use Modules\Administracion\Entities\Dispatch;
use Modules\Administracion\Entities\Vehicle;

class FleetAnalyticsService
{
    public function getAnalyticsData(): array
    {
        $vehicles = Vehicle::all();

        $dispatches = Dispatch::whereNotNull('vehicle_id')->get();

        return [
            'vehicles' => $vehicles,
            'dispatches' => $dispatches,
        ];
    }
}
