<?php

namespace Modules\Administracion\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Modules\Administracion\Entities\Warehouse;
use Modules\Administracion\Http\Requests\StoreWarehouseRequest;
use Modules\Administracion\Services\WarehouseService;
use Modules\Administracion\Transformers\WarehouseResource;

class WarehouseController extends Controller
{
    public function __construct(
        private readonly WarehouseService $warehouseService
    ) {}

    /**
     * Lista todas las bodegas.
     */
    public function index(): JsonResponse
    {
        $warehouses = Warehouse::with('responsible')->get();

        return response()->json([
            'data' => WarehouseResource::collection($warehouses)
        ]);
    }

    /**
     * Almacena una nueva bodega.
     */
    public function store(StoreWarehouseRequest $request): JsonResponse
    {
        $warehouse = $this->warehouseService->createWarehouse($request->validated());

        $warehouse->load('responsible');

        return response()->json([
            'msg' => [
                'summary' => 'Bodega creada',
                'detail' => "La bodega '{$warehouse->name}' fue registrada exitosamente.",
                'code' => 201,
            ],
            'data' => new WarehouseResource($warehouse)
        ], 201);
    }
}
