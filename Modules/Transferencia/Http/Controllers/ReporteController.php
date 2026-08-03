<?php

namespace Modules\Transferencia\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Transferencia\Services\ReporteService;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReporteController extends Controller
{
    public function __construct(
        private readonly ReporteService $reporteService
    )
    {
    }

    public function descargarDashboardPdf(Request $request)
    {
        $user = $request->user();
        $canSeeAll = $user->hasPermissionTo('transferencia.seguimiento_general');
        $filters = $request->only(['location_id', 'filter_user_id', 'provincia_id', 'canton_id', 'parroquia_id', 'limit_poas']);

        return $this->reporteService->generarReporteDashboard($user->id, $canSeeAll, $filters);
    }
}
