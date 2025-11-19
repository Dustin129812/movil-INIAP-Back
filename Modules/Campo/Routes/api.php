<?php

use Illuminate\Support\Facades\Route;
use Modules\Campo\Http\Controllers\FieldLogController;

Route::middleware('auth:api')->prefix('v1/campo')->group(function() {

    Route::post('logs', [FieldLogController::class, 'store']);

});
