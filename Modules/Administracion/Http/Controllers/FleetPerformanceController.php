<?php

namespace Modules\Administracion\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Modules\Administracion\Http\Requests\GetFleetPerformanceRequest;
use Modules\Administracion\Services\FleetAnalyticsService;
use Modules\Administracion\Transformers\FleetVehicleResource;
use Modules\Administracion\Transformers\FleetDispatchResource;

class FleetPerformanceController extends Controller
{
    public function __construct(private FleetAnalyticsService $analyticsService)
    {
    }

    public function index(GetFleetPerformanceRequest $request): JsonResponse
    {
        $data = $this->analyticsService->getAnalyticsData(
            $request->validated('start_date'),
            $request->validated('end_date')
        );

        return response()->json([
            'data' => [
                'vehicles' => FleetVehicleResource::collection($data['vehicles']),
                'requests' => FleetDispatchResource::collection($data['dispatches']),
            ]
        ]);
    }
}
