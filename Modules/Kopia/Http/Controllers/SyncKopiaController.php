<?php

namespace Modules\Kopia\Http\Controllers;

use Illuminate\Routing\Controller;
use Illuminate\Http\JsonResponse;
use Modules\Kopia\Http\Requests\SyncKopiaRequest;
use Modules\Kopia\Services\SyncKopiaService;

class SyncKopiaController extends Controller
{
    public function __construct(
        private readonly SyncKopiaService $syncService
    ) {}

    public function sync(SyncKopiaRequest $request): JsonResponse
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
