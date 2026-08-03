<?php

namespace Modules\Administracion\Http\Controllers;

use Illuminate\Routing\Controller;
use Modules\Administracion\Services\AdminDashboardService;
use Modules\Administracion\Transformers\AdminDashboardResource;

class AdministracionController extends Controller
{
    /**
     * Obtiene las métricas principales del sistema para el Dashboard.
     */
    public function metrics(AdminDashboardService $dashboardService)
    {
        $metrics = $dashboardService->getMetrics();

        return new AdminDashboardResource($metrics);
    }
}
