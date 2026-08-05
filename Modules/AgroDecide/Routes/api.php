<?php

use Illuminate\Support\Facades\Route;
// use Modules\Investigacion\Http\Controllers\GeneralController;
use Modules\AgroDecide\Http\Controllers\AuthAgroDecideController;
use Modules\AgroDecide\Http\Controllers\CatalogoController;
use Modules\AgroDecide\Http\Controllers\GuestAuthController;
use Modules\AgroDecide\Http\Controllers\LoteController;
use Modules\AgroDecide\Http\Controllers\ProyectoController;
use Modules\AgroDecide\Http\Controllers\SyncAgroDecideController;
use Modules\AgroDecide\Http\Controllers\UserAgroDecideController;

Route::prefix('agrodecide')->group(function () {

    // Rutas Públicas
    Route::post('/login', [AuthAgroDecideController::class, 'login']);
    Route::post('/guest/login', [GuestAuthController::class, 'login']);
    Route::post('/user/login', [UserAgroDecideController::class, 'login']);

    // Rutas Protegidas (Requieren Token JWT)
    Route::middleware('AgroDecide.auth.mixed')->group(function () {

        // Sincronización General (Offline-first)
        Route::get('/sync/download', [SyncAgroDecideController::class, 'download']);
        Route::post('/sync', [SyncAgroDecideController::class, 'sync']);

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
