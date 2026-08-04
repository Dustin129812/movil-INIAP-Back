<?php

use Illuminate\Http\Request;
use Modules\TrlImporter\Http\Controllers\SyncController;
use Modules\TrlImporter\Http\Controllers\SyncUpController;
use Modules\TrlImporter\Http\Controllers\TrlImporterController;

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

Route::post('/importar-matriz-excel', [TrlImporterController::class, 'upload']);

Route::get('/sync/tecnologias', [SyncController::class, 'getTecnologias']);
Route::get('/sync/matriz', [SyncController::class, 'getMatriz']);

Route::post('/sync/up', [SyncUpController::class, 'store']);
