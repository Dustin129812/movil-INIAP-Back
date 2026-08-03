<?php

use App\Http\Controllers\UbicacionController;
use Illuminate\Support\Facades\Route;

Route::middleware('jwt')->prefix('ubicacion')->group(function () {
    Route::get('/provincias', [UbicacionController::class, 'provincias']);
    Route::get('/cantones/{provinciaId}', [UbicacionController::class, 'cantones']);
    Route::get('/estaciones', [UbicacionController::class, 'estaciones']);
});
