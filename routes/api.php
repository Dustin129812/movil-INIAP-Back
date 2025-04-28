<?php

use App\Http\Controllers\UserController;
use App\Http\Controllers\ImportController;
use App\Http\Controllers\PlannerController;
use App\Http\Controllers\GroupController;
use App\Http\Controllers\GeneralController;
use App\Http\Controllers\AuthController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;


Route::post('login', [AuthController::class, 'login']);
Route::post('logout', [AuthController::class, 'logout']);

Route::middleware('auth:api')->group(callback: function () {
    Route::get('/user', function (Request $request) {
        return $request->user();
    });
    Route::get('/user/roles', [AuthController::class, 'getUserRoles']);
    Route::post('/addProductAndActivity', [PlannerController::class, 'addProductAndActivity']);
    Route::get('/getUsers', [UserController::class, 'getUsers']);
    Route::post('/users/{id}/roles', [UserController::class, 'updateRoles']);
    Route::put('week-activities/{activityId}/approve', [PlannerController::class, 'approveActivity']);
    Route::post('weeklyPlanner', [PlannerController::class, 'weeklyPlanner']);
    Route::get('getWeeklyPlanningByResponsible', [PlannerController::class, 'getWeeklyPlanningByResponsible']);

    Route::get('getProducts', [GeneralController::class, 'getProducts']);
    Route::get('products-with-activities', [GeneralController::class, 'getProductsWithActivities']);
    Route::get('activities', [GeneralController::class, 'getActivitiesByProduct']);
    Route::get('activities/{productId}', [GeneralController::class, 'getActivitiesByProduct']);

    Route::get('getProductsWithActivities', [PlannerController::class, 'getProductsWithActivities']);
    Route::get('/profile', [UserController::class, 'getProfile']);
    Route::put('/profile/update-password', [UserController::class, 'updatePassword']);

});

Route::get('/materials', [PlannerController::class, 'getMaterial']);
Route::post('importUserFile', [ImportController::class, 'importUserFile']);

Route::post('addRubro', [GeneralController::class, 'addRubro']);
Route::post('addIndicator', [GeneralController::class, 'addIndicator']);

Route::get('getLocations', [GeneralController::class, 'getLocations']);
Route::get('getNationality', [GeneralController::class, 'getNationality']);
Route::get('getEthnics', [GeneralController::class, 'getEthnics']);
Route::get('getPositions', [GeneralController::class, 'getPositions']);
Route::get('getRubros', [GeneralController::class, 'getRubros']);
Route::get('getIndicators', [GeneralController::class, 'getIndicators']);






