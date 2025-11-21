<?php

use Illuminate\Support\Facades\Route;
use Modules\Campo\Http\Controllers\ActivityController;
use Modules\Campo\Http\Controllers\FieldController;

Route::middleware('auth:api')->prefix('/campo')->group(function() {

    Route::apiResource('fields',FieldController::class);
    Route::get('activities/pending-plan', [ActivityController::class, 'getPendingPlannedActivities']);
    Route::apiResource('activities', ActivityController::class);
});
