<?php

use Illuminate\Support\Facades\Route;
use Modules\Produccion\Http\Controllers\LoteController;
use Modules\Produccion\Http\Controllers\KardexController;
use Modules\Produccion\Http\Controllers\CatalogoController;
use Modules\Produccion\Http\Controllers\LibroCampoController;

Route::prefix('produccion')->group(function() {

    // --- LIBROS DE CAMPO ---
    Route::prefix('libros-campo')->group(function() {
        Route::get('/', [LibroCampoController::class, 'index']);
        Route::post('/', [LibroCampoController::class, 'store']);
        Route::get('/{id}', [LibroCampoController::class, 'show'])->where('id', '[0-9]+');
        Route::post('/{id}/cosechar', [LibroCampoController::class, 'cosechar'])->where('id', '[0-9]+');
    });

    // --- GESTIÓN DE LOTES ---
    Route::prefix('lotes')->group(function() {
        Route::get('/', [LoteController::class, 'index']);
        Route::post('/', [LoteController::class, 'store']);
        Route::put('/{id}', [LoteController::class, 'update']);
        Route::delete('/{id}', [LoteController::class, 'destroy']);
        Route::post('/{parentId}/segmentar', [LoteController::class, 'segmentar']);
    });

    // --- KARDEX E INVENTARIOS ---
    Route::prefix('kardex')->group(function() {
        Route::get('/', [KardexController::class, 'index']);
        Route::post('/ingreso', [KardexController::class, 'ingreso']);
        Route::post('/egreso', [KardexController::class, 'egreso']);
    });

    // --- CATÁLOGOS DE SOPORTE ---
    Route::prefix('catalogos')->group(function() {
        Route::get('/', [CatalogoController::class, 'index']);
        Route::post('/bodegas', [CatalogoController::class, 'storeBodega']);
        Route::post('/unidades', [CatalogoController::class, 'storeUnidad']);
        Route::post('/insumos', [CatalogoController::class, 'storeInsumo']);
        Route::post('/maquinaria', [CatalogoController::class, 'storeMaquinaria']);
    });

    // --- ACTIVIDADES OPERATIVAS ---
    Route::prefix('actividades')->group(function() {
        Route::post('/registrar-labor', [LibroCampoController::class, 'registrarLabor']);
        Route::post('/registrar-personal', [LibroCampoController::class, 'registrarPersonal']);
        Route::post('/registrar-maquinaria', [LibroCampoController::class, 'registrarMaquinaria']);
    });
});
