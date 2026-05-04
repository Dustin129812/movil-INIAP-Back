<?php

namespace Modules\Investigacion\Http\Controllers\Reports;

use App\Http\Controllers\Controller;
use Modules\Investigacion\Http\Requests\Reports\GenerateWeeklyReportRequest;
use Modules\Investigacion\Services\Reports\WeeklyPlanReportService;
use Modules\Investigacion\Services\Reports\WeeklyMonitoringReportService;
use Modules\Investigacion\Services\Reports\WeeklyPlanDataService;
use Illuminate\Http\Request;

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
