<?php

use Illuminate\Http\Request;
use Modules\Investigacion\Http\Controllers\IdiProtocolController;
use Modules\Investigacion\Http\Controllers\MonthlyProgressController;
use Modules\Investigacion\Http\Controllers\PlannerController;
use Modules\Investigacion\Http\Controllers\PlanningReviewController;
use Modules\Investigacion\Http\Controllers\Reports\ExecutiveReportController;
use Modules\Investigacion\Http\Controllers\Reports\PerformanceReportController;
use Modules\Investigacion\Http\Controllers\Reports\SurveyReportController;
use Modules\Investigacion\Http\Controllers\Reports\WeeklyReportController;
use Modules\Investigacion\Http\Controllers\UbicacionController;
use Modules\Investigacion\Http\Controllers\WeekActivityController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::middleware('auth:api')->prefix('investigacion')->group(function () {

    Route::resource('protocols', IdiProtocolController::class);
    Route::get('catalogs/all', [IdiProtocolController::class, 'catalogs']);
    Route::get('/protocols/download/{annexId}', [IdiProtocolController::class, 'downloadAnnex']);
    Route::apiResource('protocols', IdiProtocolController::class);

    Route::get('monthly-progress/pending', [MonthlyProgressController::class, 'index']);
    Route::post('monthly-progress/store', [MonthlyProgressController::class, 'store']);
    Route::get('monthly-progress/history', [MonthlyProgressController::class, 'getReported']);

    Route::put('/planner/review-product/{id}', [PlannerController::class, 'reviewProduct']);

    Route::put('/week-activities/{activityId}/respond-support', [WeekActivityController::class, 'respondToSupport']);

    Route::get('/provincias', [UbicacionController::class, 'getProvincias']);
    Route::get('/provincias/{provinciaId}/cantones', [UbicacionController::class, 'getCantonesPorProvincia']);
    Route::get('/cantones/{cantonId}/parroquias', [UbicacionController::class, 'getParroquiasPorCanton']);

    Route::prefix('planning-reviews')->group(function () {
        Route::get('/', [PlanningReviewController::class, 'index']);
    });

    Route::put('activities/{activityId}/status', [PlanningReviewController::class, 'updateStatus']);

    Route::get('/verificables/zip-user/{userId}', [PlanningReviewController::class, 'prepareUserZip']);

    Route::put('/week-activities/{id}', [WeekActivityController::class, 'updateActivity']);


});

Route::middleware(['auth:api'])->prefix('reports')->group(function () {

    // Reportes de Operación Semanal
    Route::controller(WeeklyReportController::class)->group(function () {
        Route::get('/weekly-plan', 'generateWeeklyPlanReport');
        Route::get('/weekly-monitoring', 'generateWeeklyMonitoringReport');
        Route::get('/user-weekly-plans', 'getUserWeeklyPlans');
        Route::get('/location-weekly-plans', 'getUserWeeklyPlansbyLocation');
    });

    // Reportes de Rendimiento y Análisis
    Route::controller(PerformanceReportController::class)->group(function () {
        Route::get('/user-deep-dive/{user}', 'generateUserDeepDivePdf');
        Route::get('/user-deep-dive/{user}/data', 'getUserDeepDiveData');
        Route::get('/rubro-deep-dive/{rubro}', 'generateRubroDeepDivePdf');
        Route::get('/team-pulse', 'generateTeamPulseReport');
    });

    // Reportes Ejecutivos Nacionales
    Route::middleware('permission:view-direccion-dashboard')->group(function () {
        Route::get('/national/executive-summary', [ExecutiveReportController::class, 'generateNationalExecutiveSummary']);
        Route::get('/national/station-comparison', [ExecutiveReportController::class, 'generateStationComparisonReport']);
    });

    // Reportes de Encuestas
    Route::controller(SurveyReportController::class)->prefix('surveys/{survey}')->group(function () {
        Route::get('/export/pdf', 'exportPdf');
        Route::get('/export/excel', 'exportExcel');
    });

});

Route::get('/verificables/descargar', [PlanningReviewController::class, 'downloadEvidence'])
    ->name('api.investigacion.evidence.download');

Route::get('/verificables/descargar-zip', [PlanningReviewController::class, 'downloadZip'])
    ->name('api.investigacion.evidence.zip.download');
