<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\UserController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::post('login', [AuthController::class, 'login']);
Route::post('logout', [AuthController::class, 'logout']);

Route::group(['middleware' => ['jwt.auth'], 'prefix' => 'users'], function (){
    Route::get('/', [UserController::class, 'index']);
    Route::post('create', [UserController::class, 'save']);
    Route::put('update/{id}', [UserController::class, 'update']);
    Route::delete('delete/{id}', [UserController::class, 'delete']);
});

Route::group(['middleware' => ['jwt.auth'], 'prefix' => 'orders'], function (){
    Route::post('create', [OrderController::class, 'save']);
    Route::post('simulate', [OrderController::class, 'simulate']);
    Route::get('details/{id}', [OrderController::class, 'index']);
    Route::put('update/{id}', [OrderController::class, 'update']);
    Route::put('cancellation/{id}', [OrderController::class, 'cancel']);
});



