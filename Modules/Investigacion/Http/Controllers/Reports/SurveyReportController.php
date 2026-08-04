<?php

namespace Modules\Investigacion\Http\Controllers\Reports;

use App\Http\Controllers\Controller;
use Modules\Investigacion\Entities\Survey;
use Modules\Investigacion\Http\Controllers\SurveyController;
use Modules\Investigacion\Services\Reports\SurveyReportService;
use Illuminate\Http\Request;

class SurveyReportController extends Controller
{
    public function __construct(
        private readonly SurveyReportService $surveyService
    ) {}

    public function exportPdf(Request $request, Survey $survey)
    {
        $surveyController = new SurveyController();
        $results = json_decode($surveyController->results($request, $survey)->getContent(), true);

        return $this->surveyService->exportPdf($survey, $results);
    }

    public function exportExcel(Request $request, Survey $survey)
    {
        return $this->surveyService->exportExcel($survey);
    }
}
