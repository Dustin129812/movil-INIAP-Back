<?php

namespace Modules\Transferencia\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Transferencia\Services\DashboardService;

class DashboardController extends Controller
{
    public function __construct(
        private readonly DashboardService $dashboardService
    ) {}

    /**
     * Obtiene las métricas globales filtradas por el nivel de acceso del usuario.
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        $canSeeAll = $user->hasPermissionTo('transferencia.seguimiento_general');
        $ubicacionId = $user->location_id;
        $userId = $user->id;

        $filters = $request->only(['location_id', 'filter_user_id', 'provincia_id', 'canton_id', 'parroquia_id']);

        $metricas = $this->dashboardService->getMetricasGlobales($ubicacionId, $userId, $canSeeAll, $filters);

        return response()->json([
            'data' => $metricas
        ]);
    }

    public function poaDetails(Request $request, int $productoId): JsonResponse
    {
        $user = $request->user();
        $canSeeAll = $user->hasPermissionTo('transferencia.seguimiento_general');
        $userId = $user->id;

        $filters = $request->only(['location_id', 'filter_user_id', 'provincia_id', 'canton_id', 'parroquia_id']);

        $detalles = $this->dashboardService->getPoaDetails($productoId, $userId, $canSeeAll, $filters);

        return response()->json(['data' => $detalles]);
    }
}
