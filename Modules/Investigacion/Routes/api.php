<?php

use Illuminate\Http\Request;
use Modules\Investigacion\Http\Controllers\MonthlyProgressController;
use Modules\Investigacion\Http\Controllers\PlannerController;

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

    // Obtener actividades pendientes de reporte (filtradas por plan > 0)
    Route::get('monthly-progress/pending', [MonthlyProgressController::class, 'index']);

    // Guardar reporte (acepta evidence_url)
    Route::post('monthly-progress/store', [MonthlyProgressController::class, 'store']);

    // Ver historial de reportados
    Route::get('monthly-progress/history', [MonthlyProgressController::class, 'getReported']);

    Route::put('/planner/review-product/{id}', [PlannerController::class, 'reviewProduct']);
});
