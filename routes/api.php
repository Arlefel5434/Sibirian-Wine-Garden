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

Route::get('home', HomeController::class)->name('api.home');
Route::get('product-filters', ProductFilterOptionsController::class)->name('api.product-filters');

Route::prefix('auth')->group(function () {
    Route::post('login', [AuthController::class, 'login'])
        ->middleware('throttle:6,1');
    Route::post('refresh', [AuthController::class, 'refresh'])
        ->middleware('throttle:30,1');
    Route::post('logout', [AuthController::class, 'logout']);

    Route::middleware('auth:api')->group(function () {
        Route::get('me', [AuthController::class, 'me'])->middleware('not_banned');
    });
});

Route::name('api.')->group(function () {
    Route::apiResource('posts', PostController::class)->only(['index', 'show']);
    Route::apiResource('products', ProductController::class)->only(['index', 'show']);
});

Route::middleware(['auth:api', 'not_banned'])->name('api.')->group(function () {
    Route::get('cart', CartController::class)->name('cart.show');
    Route::post('cart/items', [CartItemController::class, 'store'])->name('cart.items.store');
    Route::patch('cart/items/{cartItem}', [CartItemController::class, 'update'])->name('cart.items.update');
    Route::delete('cart/items/{cartItem}', [CartItemController::class, 'destroy'])->name('cart.items.destroy');
    Route::post('checkout', CheckoutController::class)->middleware('throttle:10,1')->name('checkout');
    Route::get('orders', OrderController::class)->name('orders.index');
    Route::post('orders/{order}/payments', [PaymentController::class, 'store'])->name('payments.store');
    Route::get('payments/{payment}', [PaymentController::class, 'show'])->name('payments.show');
    Route::post('payments/{payment}/simulate', PaymentSimulationController::class)->name('payments.simulate.store');
    Route::get('wallet', WalletController::class)->name('wallet.show');
    Route::post('admin/users/{user}/wallet/adjustments', AdminWalletAdjustmentController::class)->name('admin.wallet.adjustments.store');
    Route::get('admin/orders', AdminOrderController::class)->name('admin.orders.index');
    Route::get('admin/users', [AdminUserController::class, 'index'])->name('admin.users.index');
    Route::patch('admin/users/{user}', [AdminUserController::class, 'update'])->name('admin.users.update');
    Route::get('favorites', [FavoriteController::class, 'index'])->name('favorites.index');
    Route::post('favorites/{product}', [FavoriteController::class, 'store'])->name('favorites.store');
    Route::delete('favorites/{product}', [FavoriteController::class, 'destroy'])->name('favorites.destroy');
    Route::patch('profile', ProfileController::class)->name('profile.update');
    Route::apiResource('posts', PostController::class)->except(['index', 'show']);
    Route::get('product-options', ProductOptionsController::class)->name('product-options');
    Route::apiResource('products', ProductController::class)->except(['index', 'show']);
});
