<?php

use App\Http\Controllers\Api\AuthController;
use Illuminate\Support\Facades\Route;

Route::prefix('auth')->group(function () {
    Route::post('login', [AuthController::class, 'login'])
        ->middleware('throttle:6,1');
    Route::post('refresh', [AuthController::class, 'refresh'])
        ->middleware('throttle:30,1');
    Route::post('logout', [AuthController::class, 'logout']);

    Route::middleware('auth:api')->group(function () {
        Route::get('me', [AuthController::class, 'me']);
    });
});
