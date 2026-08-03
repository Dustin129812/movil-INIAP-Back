<?php

use Modules\DireccionInvestigaciones\Http\Controllers\Protocolos\CatalogController;
use Modules\DireccionInvestigaciones\Http\Controllers\Protocolos\IdiProtocolController;

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

Route::middleware('auth:api')->prefix('direccioninvestigaciones')->group(function () {
    Route::prefix('protocolos')->group(function () {
        Route::get('/catalogs/all', [CatalogController::class, 'index']);
        Route::get('/download/{annexId}', [IdiProtocolController::class, 'downloadAnnex']);

        Route::apiResource('/', IdiProtocolController::class)->parameters([
            '' => 'protocol'
        ]);
    });
});
