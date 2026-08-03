<?php

use Illuminate\Support\Facades\Route;
use Modules\AgroDecide\Http\Controllers\AuthAgroDecideController;
use Modules\AgroDecide\Http\Controllers\CatalogoController;
use Modules\AgroDecide\Http\Controllers\GuestAuthController;
use Modules\AgroDecide\Http\Controllers\LoteController;
use Modules\AgroDecide\Http\Controllers\ProyectoController;
use Modules\AgroDecide\Http\Controllers\SyncAgroDecideController;
use Modules\AgroDecide\Http\Controllers\UserManagementController;

Route::prefix('agrodecide')->group(function () {

    // Rutas Públicas
    Route::post('/login', [AuthAgroDecideController::class, 'login']);
    Route::post('/guest/login', [GuestAuthController::class, 'login']);

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

        // Gestión de Lotes (funciona para usuarios e invitados)
        Route::get('/lotes', [LoteController::class, 'index']);
        Route::get('/lotes/{id}', [LoteController::class, 'show']);
        Route::post('/lotes', [LoteController::class, 'store']);
        Route::put('/lotes/{id}', [LoteController::class, 'update']);
        Route::delete('/lotes/{id}', [LoteController::class, 'destroy']);

        // Gestión de Usuarios (solo admin)
        Route::middleware('role:administrador')->group(function () {
            Route::get('/usuarios', [UserManagementController::class, 'index']);
            Route::post('/usuarios', [UserManagementController::class, 'store']);
            Route::get('/usuarios/{id}', [UserManagementController::class, 'show']);
            Route::put('/usuarios/{id}', [UserManagementController::class, 'update']);
            Route::delete('/usuarios/{id}', [UserManagementController::class, 'destroy']);
        });
    });
});
