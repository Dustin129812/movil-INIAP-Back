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

Route::middleware('auth:api')->group(function () {
    Route::post('logout', [AuthController::class, 'logout']);
});

Route::post('importUserFile', [ImportController::class, 'importUserFile']);
Route::post('addProductAndActivity', [PlannerController::class, 'addProductAndActivity']);

Route::prefix('groups')->group(function () {
    Route::post('/', [GroupController::class, 'store']); // Crear un grupo
    Route::get('{id}', [GroupController::class, 'show']); // Obtener los detalles de un grupo
    Route::put('{id}', [GroupController::class, 'update']); // Actualizar miembros de un grupo
    Route::delete('{id}', [GroupController::class, 'destroy']); // Eliminar un grupo
});


Route::get('getLocations', [GeneralController::class, 'getLocations']);
Route::get('getNationality', [GeneralController::class, 'getNationality']);
Route::get('getEthnics', [GeneralController::class, 'getEthnics']);
Route::get('getPositions', [GeneralController::class, 'getPositions']);
Route::get('getRubros', [GeneralController::class, 'getRubros']);
Route::get('getIndicators', [GeneralController::class, 'getIndicators']);
Route::get('getProducts', [GeneralController::class, 'getProducts']);
Route::get('getUsers', [UserController::class, 'getUsers']);

Route::get('activities', [GeneralController::class, 'getActivitiesByProduct']);
Route::get('activities/{productId}', [GeneralController::class, 'getActivitiesByProduct']);


Route::get('products-with-activities', [GeneralController::class, 'getProductsWithActivities']);

