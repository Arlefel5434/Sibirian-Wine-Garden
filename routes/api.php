<?php

use App\Http\Controllers\Api\AdminOrderController;
use App\Http\Controllers\Api\AdminUserController;
use App\Http\Controllers\Api\AdminWalletAdjustmentController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CartController;
use App\Http\Controllers\Api\CartItemController;
use App\Http\Controllers\Api\CheckoutController;
use App\Http\Controllers\Api\FavoriteController;
use App\Http\Controllers\Api\HomeController;
use App\Http\Controllers\Api\LocaleController;
use App\Http\Controllers\Api\OrderController;
use App\Http\Controllers\Api\PaymentController;
use App\Http\Controllers\Api\PaymentSimulationController;
use App\Http\Controllers\Api\PostController;
use App\Http\Controllers\Api\ProductController;
use App\Http\Controllers\Api\ProductFilterOptionsController;
use App\Http\Controllers\Api\ProductOptionsController;
use App\Http\Controllers\Api\ProfileController;
use App\Http\Controllers\Api\WalletController;
use Illuminate\Support\Facades\Route;

Route::name('api.')->group(function () {
    Route::get('home', HomeController::class)->name('home');
    Route::get('locales', LocaleController::class)->name('locales');
    Route::get('product-filters', ProductFilterOptionsController::class)->name('product-filters');

    Route::prefix('auth')->name('auth.')->group(function () {
        Route::post('register', [AuthController::class, 'register'])
            ->middleware('throttle:3,1')
            ->name('register');
        Route::post('email/verification-notification', [AuthController::class, 'resendVerification'])
            ->middleware('throttle:3,1')
            ->name('verification.resend');
        Route::post('login', [AuthController::class, 'login'])
            ->middleware('throttle:6,1')
            ->name('login');
        Route::post('refresh', [AuthController::class, 'refresh'])
            ->middleware('throttle:30,1')
            ->name('refresh');
        Route::post('logout', [AuthController::class, 'logout'])->name('logout');
        Route::get('me', [AuthController::class, 'me'])
            ->middleware(['auth:api', 'not_banned', 'email_verified'])
            ->name('me');
    });

    Route::apiResource('posts', PostController::class)->only(['index', 'show']);
    Route::apiResource('products', ProductController::class)->only(['index', 'show']);

    Route::middleware(['auth:api', 'not_banned', 'email_verified'])->group(function () {
        Route::prefix('cart')->name('cart.')->group(function () {
            Route::get('/', CartController::class)->name('show');
            Route::post('items', [CartItemController::class, 'store'])->name('items.store');
            Route::patch('items/{cartItem}', [CartItemController::class, 'update'])->name('items.update');
            Route::delete('items/{cartItem}', [CartItemController::class, 'destroy'])->name('items.destroy');
        });

        Route::post('checkout', CheckoutController::class)
            ->middleware('throttle:10,1')
            ->name('checkout');
        Route::get('orders', OrderController::class)->name('orders.index');
        Route::post('orders/{order}/payments', [PaymentController::class, 'store'])->name('payments.store');

        Route::prefix('payments')->name('payments.')->group(function () {
            Route::get('{payment}', [PaymentController::class, 'show'])->name('show');
            Route::post('{payment}/simulate', PaymentSimulationController::class)->name('simulate.store');
        });

        Route::get('wallet', WalletController::class)->name('wallet.show');

        Route::prefix('admin')->name('admin.')->group(function () {
            Route::get('orders', AdminOrderController::class)->name('orders.index');
            Route::get('users', [AdminUserController::class, 'index'])->name('users.index');
            Route::patch('users/{user}', [AdminUserController::class, 'update'])->name('users.update');
            Route::post('users/{user}/wallet/adjustments', AdminWalletAdjustmentController::class)
                ->name('wallet.adjustments.store');
        });

        Route::prefix('favorites')->name('favorites.')->group(function () {
            Route::get('/', [FavoriteController::class, 'index'])->name('index');
            Route::post('{product}', [FavoriteController::class, 'store'])->name('store');
            Route::delete('{product}', [FavoriteController::class, 'destroy'])->name('destroy');
        });

        Route::patch('profile', ProfileController::class)->name('profile.update');
        Route::apiResource('posts', PostController::class)->except(['index', 'show']);
        Route::get('product-options', ProductOptionsController::class)->name('product-options');
        Route::apiResource('products', ProductController::class)->except(['index', 'show']);
    });
});
