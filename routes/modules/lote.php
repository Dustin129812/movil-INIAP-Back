<?php

use App\Http\Controllers\LoteController;
use Illuminate\Support\Facades\Route;

Route::middleware('jwt')->prefix('lotes')->group(function () {
    Route::get('/', [LoteController::class, 'index']);
    Route::get('/{id}', [LoteController::class, 'show']);
    Route::post('/', [LoteController::class, 'store']);
    Route::put('/{id}', [LoteController::class, 'update']);
    Route::delete('/{id}', [LoteController::class, 'destroy']);
});
