<?php

namespace Modules\AgroDecide\Http\Controllers;

use Illuminate\Routing\Controller;
use Illuminate\Http\JsonResponse;
use Modules\AgroDecide\Http\Requests\SyncAgroDecideRequest;
use Modules\AgroDecide\Services\SyncAgroDecideService;
use Tymon\JWTAuth\Facades\JWTAuth;

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
        $payloadToken = JWTAuth::parseToken()->getPayload();
        $userId = $payloadToken->get('sub');
        $payload = $this->syncService->obtenerDatosSincronizacion($userId);

        return response()->json([
            'status' => 'success',
            'data' => $payload
        ], 200);
    }
}
