<?php

namespace Modules\Investigacion\Http\Controllers\Reports;

use App\Http\Controllers\Controller;
use App\Models\User;
use Modules\Investigacion\Entities\Rubro;
use Modules\Investigacion\Http\Requests\Reports\DateRangeRequest;
use Modules\Investigacion\Services\Reports\PerformanceReportService;
use Modules\Investigacion\Services\Reports\TeamPulseReportService;
use Illuminate\Http\Request;

class PerformanceReportController extends Controller
{
    public function __construct(
        private readonly PerformanceReportService $performanceService,
        private readonly TeamPulseReportService $pulseService
    ) {}

    public function generateUserDeepDivePdf(DateRangeRequest $request, User $user)
    {
        return $this->performanceService->generateUserDeepDivePdf($request->validated(), $user);
    }

    public function getUserDeepDiveData(DateRangeRequest $request, User $user)
    {
        return response()->json(['data' => $this->performanceService->getUserDeepDiveData($request->validated(), $user)]);
    }

    public function generateRubroDeepDivePdf(Rubro $rubro)
    {
        return $this->performanceService->generateRubroDeepDivePdf($rubro, auth()->user());
    }

    public function generateTeamPulseReport(Request $request)
    {
        return $this->pulseService->generateTeamPulseReport($request->user());
    }
}
