<?php

namespace Modules\Transferencia\Http\Controllers;

use Illuminate\Routing\Controller;
use Illuminate\Http\JsonResponse;
use Modules\Transferencia\Services\DashboardService;

class DashboardController extends Controller
{
    public function __construct(
        private readonly DashboardService $dashboardService
    ) {}

    /**
     * Obtiene las métricas globales para el Centro de Comando Geoespacial.
     */
    public function index(): JsonResponse
    {
        $metricas = $this->dashboardService->getMetricasGlobales();

        return response()->json([
            'data' => $metricas
        ]);
    }
}
