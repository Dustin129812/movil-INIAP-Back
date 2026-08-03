<?php

use Illuminate\Support\Facades\Route;
use Modules\Investigacion\Http\Controllers\NotificationController;

/*
|--------------------------------------------------------------------------
| API Routes - Módulo Notificaciones
|--------------------------------------------------------------------------
| Estas rutas están protegidas mediante la verificación de tokens JWT.
| El prefijo /api/notificaciones se gestiona automáticamente por el módulo.
|--------------------------------------------------------------------------
*/

Route::middleware('auth:api')->group(function () {

    Route::get('/', [NotificationController::class, 'index'])
        ->name('api.notificaciones.index');

    Route::patch('/{id}/read', [NotificationController::class, 'markAsRead'])
        ->name('api.notificaciones.mark-read');

    Route::post('/read-all', [NotificationController::class, 'markAllAsRead'])
        ->name('api.notificaciones.mark-all-read');

});
