<?php

use App\Http\Controllers\UserController;
use App\Http\Controllers\ImportController;
use App\Http\Controllers\PlannerController;
use App\Http\Controllers\WeekActivityController;
use App\Http\Controllers\GeneralController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\ChartController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;


Route::post('login', [AuthController::class, 'login']);

Route::middleware('auth:api', 'throttle:60,1')->group(callback: function () {
    Route::get('/user', function (Request $request) {
        return $request->user();
    });
    Route::post('logout', [AuthController::class, 'logout']);
    Route::get('user/roles', [AuthController::class, 'getUserRoles']);

    Route::post('users/{id}/roles', [UserController::class, 'updateRoles']);
    Route::get('getUsers', [UserController::class, 'getUsers']);
    Route::get('profile', [UserController::class, 'getProfile']);
    Route::put('profile/update-password', [UserController::class, 'updatePassword']);

    Route::post('addProductAndActivity', [PlannerController::class, 'addProductAndActivity']);
    Route::put('week-activities/{activityId}/approve', [PlannerController::class, 'approveActivity']);
    Route::get('getWeeklyPlanningByResponsible', [PlannerController::class, 'getWeeklyPlanningByResponsible']);
    Route::get('getProductsWithActivities', [PlannerController::class, 'getProductsWithActivities']);
    Route::get('materials', [PlannerController::class, 'getMaterial']);

    Route::get('getProducts', [GeneralController::class, 'getProducts']);
    Route::get('products-with-activities', [GeneralController::class, 'getProductsWithActivities']);
    Route::get('activities', [GeneralController::class, 'getActivitiesByProduct'] );
    Route::get('activities/{productId}', [GeneralController::class, 'getActivitiesByProduct']);

    Route::get('chart-data', [ChartController::class, 'getChartData']);

    Route::post('weeklyPlanner', [WeekActivityController::class, 'weeklyPlanner']);
    Route::get('week-activities/previous', [WeekActivityController::class, 'getPreviousWeekActivities']);
    Route::put('week-activities/progress', [WeekActivityController::class, 'updateWeeklyProgress']);
    Route::get('activities/progress', [WeekActivityController::class, 'getActivitiesWithProgress']);

    Route::post('import/excel', [ImportController::class, 'importProcessedData']);

    Route::post('addRubro', [GeneralController::class, 'addRubro']);
    Route::post('addIndicator', [GeneralController::class, 'addIndicator']);
    Route::get('getLocations', [GeneralController::class, 'getLocations']);
    Route::get('getNationality', [GeneralController::class, 'getNationality']);
    Route::get('getEthnics', [GeneralController::class, 'getEthnics']);
    Route::get('getPositions', [GeneralController::class, 'getPositions']);
    Route::get('getRubros', [GeneralController::class, 'getRubros']);
    Route::get('getIndicators', [GeneralController::class, 'getIndicators']);
});
