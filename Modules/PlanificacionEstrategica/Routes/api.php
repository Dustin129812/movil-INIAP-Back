<?php

use Illuminate\Support\Facades\Route;
use Modules\PlanificacionEstrategica\Http\Controllers\DashboardController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

Route::middleware('auth:api')->prefix('strategic')->group(function() {

    Route::get('/overview', [DashboardController::class, 'getGlobalOverview']);

});
