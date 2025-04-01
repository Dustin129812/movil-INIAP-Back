<?php

use App\Http\Controllers\UserController;
use App\Http\Controllers\ImportController;
use App\Http\Controllers\PlannerController;
use App\Http\Controllers\GroupController;
use App\Http\Controllers\AuthController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;


Route::post('login', [AuthController::class, 'login']);

Route::middleware('auth:api')->group(function () {
    Route::post('logout', [AuthController::class, 'logout']);
});

Route::get('getUsers', [UserController::class, 'getUsers']);
Route::post('importUserFile', [ImportController::class, 'importUserFile']);
Route::get('getObjetive', [PlannerController::class, 'getObjetive']);
Route::get('getActivity', [PlannerController::class, 'getActivity']);
Route::post('addObjetive', [PlannerController::class, 'addObjetive']);
Route::post('addActivity', [PlannerController::class, 'addActivity']);
Route::post('addPei', [PlannerController::class, 'addPei']);

Route::prefix('groups')->group(function () {
    Route::post('/', [GroupController::class, 'store']); // Crear un grupo
    Route::get('{id}', [GroupController::class, 'show']); // Obtener los detalles de un grupo
    Route::put('{id}', [GroupController::class, 'update']); // Actualizar miembros de un grupo
    Route::delete('{id}', [GroupController::class, 'destroy']); // Eliminar un grupo
});


Route::get('getLocations', [UserController::class, 'getLocations']);
Route::get('getNationality', [UserController::class, 'getNationality']);
Route::get('getEthnics', [UserController::class, 'getEthnics']);
Route::get('getPositions', [UserController::class, 'getPositions']);
