<?php

use App\Http\Controllers\UserController;
use App\Http\Controllers\ImportController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('getUsers', [UserController::class, 'getUsers']);
Route::post('importUserFile', [ImportController::class, 'importUserFile']);

Route::get('getLocations', [UserController::class, 'getLocations']);
Route::get('getNationality', [UserController::class, 'getNationality']);
Route::get('getEthnics', [UserController::class, 'getEthnics']);
Route::get('getPositions', [UserController::class, 'getPositions']);
