<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route; // Asegúrate de tener este import
use Modules\Administracion\Http\Controllers\PoaVisibilityController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

Route::middleware('auth:api')->prefix('administracion')->group(function () {

    Route::middleware('permission:admin.config.gestionar,api')->group(function() {
        Route::get('/poa-visibility', [PoaVisibilityController::class, 'index']);
        Route::post('/poa-visibility', [PoaVisibilityController::class, 'sync']);
    });

});
