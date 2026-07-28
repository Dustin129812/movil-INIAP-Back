<?php

use Illuminate\Support\Facades\Route;

Route::get('/prueba', function () {
    return response()->json([
        "mensaje" => "API Laravel funcionando"
    ]);
});

require __DIR__ . '/modules/auth.php';
require __DIR__ . '/modules/user.php';
require __DIR__ . '/modules/dispositivo.php';
