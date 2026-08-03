<?php

namespace Modules\AgroDecide\Http\Controllers;

use Illuminate\Routing\Controller;
use Illuminate\Http\JsonResponse;
use Modules\AgroDecide\Http\Requests\SyncAgroDecideRequest;
use Modules\AgroDecide\Services\SyncAgroDecideService;

class SyncAgroDecideController extends Controller
{
    public function __construct(
        private readonly SyncAgroDecideService $syncService
    ) {}

    public function sync(SyncAgroDecideRequest $request): JsonResponse
    {
        $estadisticas = $this->syncService->procesarSincronizacion(
            $request->validated('lotes')
        );

        return response()->json([
            'status' => 'success',
            'message' => 'Sincronización completada exitosamente.',
            'data' => $estadisticas
        ], 200);
    }

    public function download(): JsonResponse
    {
        $payload = $this->syncService->obtenerDatosSincronizacion(auth('api')->id());

        return response()->json([
            'status' => 'success',
            'data' => $payload
        ], 200);
    }
}
