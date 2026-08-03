<?php

namespace Modules\Administracion\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Administracion\Http\Requests\StoreVehicleRequest;
use Modules\Administracion\Services\VehicleManagementService;
use Modules\Administracion\Transformers\VehicleResource;

class VehicleManagementController extends Controller
{
    public function __construct(
        private readonly VehicleManagementService $vehicleService
    ) {}

    public function store(StoreVehicleRequest $request): JsonResponse
    {
        $vehicle = $this->vehicleService->storeVehicle(
            $request->validated(),
            $request->user()
        );

        return response()->json([
            'msg' => [
                'summary' => 'Vehículo registrado',
                'detail' => 'La unidad ha sido agregada a la flota de su estación.',
                'code' => 201,
            ],
            'data' => new VehicleResource($vehicle)
        ], 201);
    }

    public function toggleStatus(int $id, Request $request): JsonResponse
    {
        $vehicle = $this->vehicleService->toggleVehicleStatus($id, $request->user());

        $estado = $vehicle->is_active ? 'activado' : 'inactivado';

        return response()->json([
            'msg' => [
                'summary' => 'Estado actualizado',
                'detail' => "El vehículo ha sido {$estado} exitosamente.",
                'code' => 200,
            ],
            'data' => new VehicleResource($vehicle)
        ]);
    }
}
