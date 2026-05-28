<?php

use Illuminate\Support\Facades\Route;
use Modules\Administracion\Http\Controllers\DispatchController;
use Modules\Administracion\Http\Controllers\FleetPerformanceController;
use Modules\Administracion\Http\Controllers\LogisticsCatalogController;
use Modules\Administracion\Http\Controllers\PoaVisibilityController;
use Modules\Administracion\Http\Controllers\VehicleManagementController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|*/
Route::prefix('administracion')->middleware(['auth:api'])->group(function () {

    // Rutas de Catálogos
    Route::get('/logistics-catalogs', [LogisticsCatalogController::class, 'index']);

    // Rutas de Despachos
    Route::get('/dispatches', [DispatchController::class, 'index']);
    Route::post('/dispatches', [DispatchController::class, 'store']);
    Route::get('/dispatches/{id}', [DispatchController::class, 'show']);

    // Rutas de Visibilidad POA
    Route::get('/poa-visibility', [PoaVisibilityController::class, 'index']);
    Route::post('/poa-visibility', [PoaVisibilityController::class, 'sync']);

    Route::post('/vehicles', [VehicleManagementController::class, 'store']);
    Route::patch('/vehicles/{id}/toggle', [VehicleManagementController::class, 'toggleStatus']);

    Route::get('/fleet-performance', [FleetPerformanceController::class, 'index']);
});
