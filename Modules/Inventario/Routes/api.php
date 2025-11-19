<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Modules\Inventario\Http\Controllers\CategoryController;
use Modules\Inventario\Http\Controllers\InventoryController;
use Modules\Inventario\Http\Controllers\MachineryController;
use Modules\Inventario\Http\Controllers\ProductController;


/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

Route::middleware('auth:api')->group(function () {

    Route::prefix('inventario')->group(function () {

        // Rutas de Maquinaria (CRUD completo)
        Route::apiResource('machinery', MachineryController::class);

        // Rutas de Productos (CRUD completo)
        Route::apiResource('products', ProductController::class);

        // Ruta extra para agregar lotes a un producto específico
        Route::post('products/{id}/batch', [ProductController::class, 'addBatch']);

        // Ruta para las alertas del Dashboard
        Route::get('alerts', [InventoryController::class, 'alerts']);
        Route::post('products/{id}/consume', [InventoryController::class, 'consumeProduct']);
        Route::get('dashboard-stats', [InventoryController::class, 'getDashboardStats']);

        Route::apiResource('categories',CategoryController::class);
    });

});
