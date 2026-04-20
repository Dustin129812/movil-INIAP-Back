<?php

use Illuminate\Support\Facades\Route;
use Modules\Administracion\Http\Controllers\DispatchController;
use Modules\Administracion\Http\Controllers\PoaVisibilityController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|*/
Route::prefix('administracion')->middleware(['auth:api'])->group(callback: function () {

    Route::get('/dispatches', [DispatchController::class, 'index']);

    Route::post('/dispatches', [DispatchController::class, 'store']);

    Route::get('/dispatches/{id}', [DispatchController::class, 'show']);

    Route::get('/poa-visibility', [PoaVisibilityController::class, 'index']);
    Route::post('/poa-visibility', [PoaVisibilityController::class, 'sync']);

});
