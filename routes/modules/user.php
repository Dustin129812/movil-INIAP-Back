<?php

use App\Modules\User\Controllers\UserController;
use Illuminate\Support\Facades\Route;

Route::middleware('jwt')->prefix('user')->group(function () {
    Route::get('/profile', [UserController::class, 'profile']);
});
