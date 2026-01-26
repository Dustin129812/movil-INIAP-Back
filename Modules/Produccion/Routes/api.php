<?php

use Illuminate\Support\Facades\Route;
use Modules\Produccion\Http\Controllers\ProductionBatchController;
use Modules\Produccion\Http\Controllers\VarietyController;
use Modules\Produccion\Http\Controllers\ProtocolController;
use Modules\Produccion\Http\Controllers\ProductionPlanController;
use Modules\Produccion\Http\Controllers\CategoryController;
use Modules\Produccion\Http\Controllers\VarietyTypeController;
use Modules\Produccion\Http\Controllers\LotController;
/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

Route::middleware('auth:api')->prefix('produccion')->group(function() {

    Route::get('varieties', [VarietyController::class, 'index']);
    Route::post('varieties', [VarietyController::class, 'store']);

    Route::get('protocols', [ProtocolController::class, 'index']);
    Route::get('protocols/{id}', [ProtocolController::class, 'show']);
    Route::post('protocols', [ProtocolController::class, 'store']);

    Route::get('batches', [ProductionBatchController::class, 'index']);
    Route::post('batches', [ProductionBatchController::class, 'store']);

    Route::get('batches/{id}/financial-report', [ProductionBatchController::class, 'getBatchFinancialReport']);
    Route::get('batches/{id}/suggestions', [ProductionBatchController::class, 'getSuggestedActivities']);
    Route::get('dashboard-stats', [ProductionBatchController::class, 'getGlobalStats']);

    Route::apiResource('production-plans', ProductionPlanController::class);
    Route::apiResource('varieties', VarietyController::class);
    Route::apiResource('categories', CategoryController::class);
    Route::apiResource('variety-types', VarietyTypeController::class);
    Route::apiResource('lots', LotController::class);
});
