<?php

use Illuminate\Support\Facades\Route;
use Modules\Investigacion\Http\Controllers\GeneralController;
use Modules\Kopia\Http\Controllers\AuthKopiaController;
use Modules\Kopia\Http\Controllers\CatalogoController;
use Modules\Kopia\Http\Controllers\GuestAuthController;
use Modules\Kopia\Http\Controllers\LoteController;
use Modules\Kopia\Http\Controllers\ProyectoController;
use Modules\Kopia\Http\Controllers\SyncKopiaController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
| Aquí es donde puedes registrar las rutas API para tu módulo.
| Estas rutas son cargadas por el RouteServiceProvider dentro de un grupo
| que tiene asignado el middleware "api".
*/

Route::prefix('kopia')->group(function () {

    // Rutas Públicas
    Route::post('/login', [AuthKopiaController::class, 'login']);
    Route::post('/guest/login', [GuestAuthController::class, 'login']);

    // Rutas Protegidas (Requieren Token JWT)
    Route::middleware('kopia.auth.mixed')->group(function () {

        // Sincronización General (Offline-first)
        Route::get('/sync/download', [SyncKopiaController::class, 'download']);
        Route::post('/sync', [SyncKopiaController::class, 'sync']);

        // Catálogos Maestros
        Route::get('/catalogos', [CatalogoController::class, 'index']);
        Route::post('/cultivos', [CatalogoController::class, 'storeCultivo']);
        Route::post('/variedades', [CatalogoController::class, 'storeVariedad']);
        Route::get('/catalogosMobile', [CatalogoController::class, 'syncCatalogosMobile']);

        // Gestión de Proyectos
        Route::get('/proyectos', [ProyectoController::class, 'index']);
        Route::post('/proyectos', [ProyectoController::class, 'store']);
        Route::get('/proyectos/{id}', [ProyectoController::class, 'show']);

        // Dashboard Cartográfico (Web)
        Route::get('/lotes', [LoteController::class, 'index']);
        Route::get('/lotes/{id}', [LoteController::class, 'show']);
        Route::post('/lotes', [LoteController::class, 'store']);
        Route::put('/lotes/{id}', [LoteController::class, 'update']);
        Route::delete('/lotes/{id}', [LoteController::class, 'destroy']);

        Route::get('getLocations', [GeneralController::class, 'getLocations']);
    });
});
