<?php

use Illuminate\Support\Facades\Route;
use Modules\Produccion\Http\Controllers\ProductionBatchController;
use Modules\Produccion\Http\Controllers\VarietyController;
use Modules\Produccion\Http\Controllers\ProtocolController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

Route::middleware('auth:api')->prefix('produccion')->group(function() {

    // 1. Gestión de Variedades (Naranjilla, Durazno...)
    Route::get('varieties', [VarietyController::class, 'index']);
    Route::post('varieties', [VarietyController::class, 'store']);

    // 2. Gestión de Protocolos (Las Recetas Maestras)
    Route::get('protocols', [ProtocolController::class, 'index']);
    Route::get('protocols/{id}', [ProtocolController::class, 'show']); // Ver detalle de receta
    Route::post('protocols', [ProtocolController::class, 'store']); // Guardar receta completa

    Route::get('batches', [ProductionBatchController::class, 'index']);
    Route::post('batches', [ProductionBatchController::class, 'store']);

    Route::get('batches/{id}/suggestions', [ProductionBatchController::class, 'getSuggestedActivities']);
});
