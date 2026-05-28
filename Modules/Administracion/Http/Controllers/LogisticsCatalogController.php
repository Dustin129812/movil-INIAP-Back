<?php

namespace Modules\Administracion\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Administracion\Services\LogisticsCatalogService;
use Modules\Administracion\Transformers\VehicleResource;
use Modules\Administracion\Transformers\DriverResource;

class LogisticsCatalogController extends Controller
{
    public function __construct(
        private readonly LogisticsCatalogService $catalogService
    ) {}

    public function index(Request $request): JsonResponse
    {
        $locationId = $request->user()->location_id;

        $includeInactive = $request->boolean('include_inactive');

        $vehicles = $this->catalogService->getVehiclesByLocation($locationId, $includeInactive);
        $drivers = $this->catalogService->getDriversByLocation($locationId);

        return response()->json([
            'data' => [
                'vehicles' => VehicleResource::collection($vehicles),
                'drivers' => DriverResource::collection($drivers),
            ]
        ]);
    }
}
