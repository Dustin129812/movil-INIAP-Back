<?php

use Illuminate\Http\Request;
use Modules\Investigacion\Http\Controllers\IdiProtocolController;
use Modules\Investigacion\Http\Controllers\MonthlyProgressController;
use Modules\Investigacion\Http\Controllers\PlannerController;
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

    // Rutas personalizadas PRIMERO
    Route::get('catalogs/all', [IdiProtocolController::class, 'catalogs']);

    // Rutas estándar CRUD (index, store, show, update, destroy)
    Route::get('/protocols/download/{annexId}', [IdiProtocolController::class, 'downloadAnnex']);

    Route::apiResource('protocols', IdiProtocolController::class);

    // Obtener actividades pendientes de reporte (filtradas por plan > 0)
    Route::get('monthly-progress/pending', [MonthlyProgressController::class, 'index']);

    // Guardar reporte (acepta evidence_url)
    Route::post('monthly-progress/store', [MonthlyProgressController::class, 'store']);

    // Ver historial de reportados
    Route::get('monthly-progress/history', [MonthlyProgressController::class, 'getReported']);

    Route::put('/planner/review-product/{id}', [PlannerController::class, 'reviewProduct']);

    Route::put('/week-activities/{activityId}/respond-support', [WeekActivityController::class, 'respondToSupport']);
});
