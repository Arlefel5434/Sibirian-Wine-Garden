<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\HomeController;
use App\Http\Controllers\Api\PostController;
use App\Http\Controllers\Api\ProfileController;
use App\Http\Controllers\Api\ProductController;
use App\Http\Controllers\Api\ProductOptionsController;
use Illuminate\Support\Facades\Route;

Route::get('home', HomeController::class)->name('api.home');

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

Route::name('api.')->group(function () {
    Route::apiResource('posts', PostController::class)->only(['index', 'show']);
    Route::apiResource('products', ProductController::class)->only(['index', 'show']);
});

Route::middleware('auth:api')->name('api.')->group(function () {
    Route::patch('profile', ProfileController::class)->name('profile.update');
    Route::apiResource('posts', PostController::class)->except(['index', 'show']);
    Route::get('product-options', ProductOptionsController::class)->name('product-options');
    Route::apiResource('products', ProductController::class)->except(['index', 'show']);
});
