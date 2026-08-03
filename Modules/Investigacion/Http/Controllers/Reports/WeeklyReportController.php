<?php

namespace Modules\Investigacion\Http\Controllers\Reports;

use App\Http\Controllers\Controller;
use Modules\Investigacion\Http\Requests\Reports\GenerateWeeklyReportRequest;
use Modules\Investigacion\Services\Reports\WeeklyPlanReportService;
use Modules\Investigacion\Services\Reports\WeeklyMonitoringReportService;
use Modules\Investigacion\Services\Reports\WeeklyPlanDataService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;

class WeeklyReportController extends Controller
{
    public function __construct(
        private readonly WeeklyPlanReportService $planService,
        private readonly WeeklyMonitoringReportService $monitoringService,
        private readonly WeeklyPlanDataService $dataService
    ) {}

    public function generateWeeklyPlanReport(GenerateWeeklyReportRequest $request)
    {
        return $this->planService->generateReport($request->validated());
    }

    /**
     * Usa Request estándar para omitir la validación de user_id
     */
    public function generateMassiveWeeklyPlans(Request $request)
    {
        try {
            // Validamos únicamente las fechas requeridas para el filtro global
            $validated = $request->validate([
                'start_date' => 'required|date',
                'end_date' => 'required|date|after_or_equal:start_date',
            ]);

            $zipFileName = $this->planService->generateMassivePlanZip($request->user(), $validated);

            $url = URL::temporarySignedRoute(
                'api.investigacion.evidence.zip.download',
                now()->addMinutes(30),
                ['filename' => $zipFileName]
            );

            return response()->json([
                'msg' => [
                    'summary' => 'Archivo compilado',
                    'detail' => 'Los planes de la estación están listos para descarga.',
                    'code' => 200
                ],
                'url' => $url
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'msg' => [
                    'summary' => 'Error de compilación',
                    'detail' => $e->getMessage(),
                    'code' => 500
                ]
            ], 500);
        }
    }

    public function generateWeeklyMonitoringReport(GenerateWeeklyReportRequest $request)
    {
        return $this->monitoringService->generateReport($request->validated());
    }

    public function getUserWeeklyPlans(Request $request)
    {
        return response()->json($this->dataService->getUserPlans(auth()->user(), $request->all()));
    }

    public function getUserWeeklyPlansbyLocation(Request $request)
    {
        return response()->json($this->dataService->getPlansByLocation(auth()->user(), $request->all()));
    }
}
