<?php

use App\Modules\Dispositivo\Controllers\DispositivoController;
use Illuminate\Support\Facades\Route;

Route::middleware('jwt')->prefix('dispositivo')->group(function () {
    Route::get('/', [DispositivoController::class, 'index']);
    Route::get('/{id}', [DispositivoController::class, 'show']);
});
