<?php

use Illuminate\Support\Facades\Route;
use Modules\Administracion\Http\Controllers\DispatchController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|*/
Route::prefix('administracion')->middleware(['auth:api'])->group(function () {

    // 1. Obtener la lista de solicitudes (Kanban de Despachos)
    Route::get('/dispatches', [DispatchController::class, 'index']);

    // 2. Procesar un despacho (El administrador confirma cantidades y entrega)
    Route::post('/dispatches', [DispatchController::class, 'store']);

    // 3. Ver detalle histórico de un despacho específico (opcional pero recomendado)
    Route::get('/dispatches/{id}', [DispatchController::class, 'show']);

});
