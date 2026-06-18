<?php

namespace Modules\Administracion\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Administracion\Entities\Dispatch;
use Modules\Administracion\Http\Requests\GetStationRequestsRequest;
use Modules\Administracion\Http\Requests\ProcessDispatchRequest;
use Modules\Administracion\Services\DispatchService;
use Modules\Administracion\Transformers\DispatchResource;
use Modules\Administracion\Transformers\StationRequestResource;

class DispatchController extends Controller
{
    public function __construct(
        private readonly DispatchService $dispatchService
    ) {}

    /**
     * Lista todas las solicitudes para el Kanban de Administración.
     * Retorna las 3 columnas: Pendientes, En Proceso y Despachados.
     */
    public function index(GetStationRequestsRequest $request): JsonResponse
    {
        $user = $request->user();

        $validated = $request->validated();
        $locationId = $validated['location_id'] ?? $user->location_id;
        $startDate = $validated['start_date'] ?? null;
        $endDate = $validated['end_date'] ?? null;

        $requests = $this->dispatchService->getStationRequests($locationId, $startDate, $endDate);

        return response()->json([
            'msg' => [
                'summary' => 'Solicitudes obtenidas',
                'detail' => 'Cargando tablero para la ubicación seleccionada.',
                'code' => 200,
            ],
            'data' => StationRequestResource::collection($requests)
        ]);
    }

    /**
     * Procesa la gestión del administrador (Aprobar/Despachar/Rechazar).
     */
    public function store(ProcessDispatchRequest $request): JsonResponse
    {
        $dispatch = $this->dispatchService->processDispatch(
            $request->validated(),
            $request->user()
        );

        return response()->json([
            'msg' => [
                'summary' => 'Operación exitosa',
                'detail' => 'El estado del despacho ha sido actualizado.',
                'code' => 200,
            ],
            'data' => new DispatchResource($dispatch)
        ]);
    }

    /**
     * Detalle de un despacho específico.
     */
    public function show($id): JsonResponse
    {
        $dispatch = Dispatch::with('weekActivity.user')
            ->findOrFail($id);

        return response()->json([
            'data' => new DispatchResource($dispatch)
        ]);
    }
}
