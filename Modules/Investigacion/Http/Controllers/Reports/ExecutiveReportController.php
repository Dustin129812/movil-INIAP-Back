<?php

namespace Modules\Investigacion\Http\Controllers\Reports;

use App\Http\Controllers\Controller;
use Modules\Investigacion\Services\Reports\ExecutiveReportService;
use Modules\Investigacion\Http\Controllers\NationalDashboardController;
use Illuminate\Http\Request;

class ExecutiveReportController extends Controller
{
    public function __construct(
        private readonly ExecutiveReportService $executiveService
    ) {}

    public function generateNationalExecutiveSummary()
    {
        return $this->executiveService->generateNationalExecutiveSummary();
    }

    public function generateStationComparisonReport(Request $request)
    {
        // Orquestación: El controlador obtiene la data necesaria de otros controladores/servicios si es necesario
        $dashboardController = new NationalDashboardController();
        $performanceData = (array) $dashboardController->getStationPerformance($request)->getData()->data;

        return $this->executiveService->generateStationComparisonReport($performanceData);
    }
}
