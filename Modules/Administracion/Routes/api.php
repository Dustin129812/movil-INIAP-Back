<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route; // Asegúrate de tener este import
use Modules\Administracion\Http\Controllers\PoaVisibilityController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

// CORRECCIÓN: Usamos 'prefix' en lugar de 'get' para agrupar
Route::middleware('auth:api')->prefix('administracion')->group(function () {

    Route::middleware('permission:admin.config.gestionar,api')->group(function() {
        // Estas rutas ahora serán: /api/administracion/poa-visibility
        Route::get('/poa-visibility', [PoaVisibilityController::class, 'index']);
        Route::post('/poa-visibility', [PoaVisibilityController::class, 'sync']);
    });

});
