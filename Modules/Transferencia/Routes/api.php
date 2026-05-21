<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Modules\Transferencia\Http\Controllers\AcuerdoController;
use Modules\Transferencia\Http\Controllers\DashboardController;
use Modules\Transferencia\Http\Controllers\DpaController;
use Modules\Transferencia\Http\Controllers\EnsayoController;
use Modules\Transferencia\Http\Controllers\OrganizacionController;
use Modules\Transferencia\Http\Controllers\ParcelaController;
use Modules\Transferencia\Http\Controllers\TransferenciaFiltroController;

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

Route::middleware(['auth:api'])->prefix('transferencia')->group(function() {

    Route::apiResource('organizaciones', OrganizacionController::class)->parameters([
        'organizaciones' => 'organizacion'
    ]);

    Route::apiResource('acuerdos', AcuerdoController::class);
    Route::apiResource('ensayos', EnsayoController::class);
    Route::apiResource('parcelas', ParcelaController::class);

    Route::get('archivos/{tipo}/{id}', function($tipo, $id) {
    })->name('api.transferencia.archivos.descargar');

    Route::post('/dpa/importar', [DpaController::class, 'importar'])
        ->name('api.ubicacion.dpa.importar');

    Route::get('dashboard', [DashboardController::class, 'index'])
        ->name('api.transferencia.dashboard');

    /**
     * Rutas para Filtros Geográficos Optimizados (DPA Activos)
     */
    Route::prefix('filtros')->group(function () {
        Route::get('provincias', [TransferenciaFiltroController::class, 'provincias']);
        Route::get('cantones/{provincia_id}', [TransferenciaFiltroController::class, 'cantones']);
        Route::get('parroquias/{canton_id}', [TransferenciaFiltroController::class, 'parroquias']);
    });

});

Route::get('/acuerdos/descargar/{acuerdo}', [AcuerdoController::class, 'download'])
    ->name('api.transferencia.acuerdos.download');
