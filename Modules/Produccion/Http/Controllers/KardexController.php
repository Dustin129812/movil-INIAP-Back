<?php

namespace Modules\Produccion\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Produccion\Entities\Kardex;
use Modules\Produccion\Services\KardexService;
use Modules\Produccion\Traits\ApiResponse;
use Modules\Produccion\Transformers\KardexResource;
use Modules\Produccion\Http\Requests\Kardex\IngresoKardexRequest;
use Modules\Produccion\Http\Requests\Kardex\EgresoKardexRequest;
use Exception;

class KardexController extends Controller
{
    use ApiResponse;

    public function __construct(
        protected KardexService $kardexService
    ) {}

    public function index(Request $request): JsonResponse
    {
        $query = Kardex::with(['bodega', 'insumo'])->latest();

        if ($request->has('bodega_id')) $query->where('bodega_id', $request->bodega_id);
        if ($request->has('insumo_id')) $query->where('insumo_id', $request->insumo_id);

        $movimientos = $query->paginate(20);

        return KardexResource::collection($movimientos)->response();
    }

    public function ingreso(IngresoKardexRequest $request): JsonResponse
    {
        $v = $request->validated();

        $movimiento = $this->kardexService->registrarIngreso(
            $v['bodega_id'],
            $v['insumo_id'],
            $v['cantidad'],
            $v['costo_unitario'], $v['documento_referencia'] ?? null
        );

        return $this->createdResponse(
            new KardexResource($movimiento->load(['bodega', 'insumo'])),
            'Ingreso a bodega registrado con éxito.'
        );
    }

    public function egreso(EgresoKardexRequest $request): JsonResponse
    {
        $v = $request->validated();

        try {
            $movimiento = $this->kardexService->registrarEgreso(
                $v['bodega_id'],
                $v['insumo_id'],
                $v['cantidad'],
                $v['documento_referencia']
            );

            return $this->createdResponse(
                new KardexResource($movimiento->load(['bodega', 'insumo'])),
                'Egreso registrado. Costo calculado automáticamente.'
            );

        } catch (Exception $e) {
            return $this->errorResponse($e->getMessage(), 422);
        }
    }
}
