<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\UserController;

Route::get('/prueba', function () {
    return response()->json([
        "mensaje" => "API Laravel funcionando"
    ]);
});

/*
|--------------------------------------------------------------------------
| Rutas públicas
|--------------------------------------------------------------------------
*/

Route::post('/login', [AuthController::class, 'login']);

/*
|--------------------------------------------------------------------------
| Rutas protegidas con JWT
|--------------------------------------------------------------------------
*/

Route::middleware('jwt')->group(function () {

    Route::get('/me', [AuthController::class, 'me']);

    Route::post('/logout', [AuthController::class, 'logout']);

    Route::post('/refresh', [AuthController::class, 'refresh']);

});

Route::middleware('jwt')->group(function () {

    Route::get('/me', [AuthController::class, 'me']);

    Route::get('/profile', [UserController::class, 'profile']);

    Route::post('/logout', [AuthController::class, 'logout']);

});