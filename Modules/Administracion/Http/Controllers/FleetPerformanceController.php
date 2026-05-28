<?php

namespace Modules\Administracion\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Modules\Administracion\Services\FleetAnalyticsService;
use Modules\Administracion\Transformers\FleetVehicleResource;
use Modules\Administracion\Transformers\FleetDispatchResource;

class FleetPerformanceController extends Controller
{
    private FleetAnalyticsService $analyticsService;

    public function __construct(FleetAnalyticsService $analyticsService)
    {
        $this->analyticsService = $analyticsService;
    }

    public function index(): JsonResponse
    {
        $data = $this->analyticsService->getAnalyticsData();

        return response()->json([
            'data' => [
                'vehicles' => FleetVehicleResource::collection($data['vehicles']),
                'requests' => FleetDispatchResource::collection($data['dispatches']),
            ]
        ]);
    }
}
