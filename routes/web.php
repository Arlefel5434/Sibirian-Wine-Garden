<?php

use App\Http\Controllers\Auth\VerifyEmailController;
use App\Http\Controllers\Web\HomeController;
use App\Http\Controllers\Web\PostPageController;
use App\Http\Controllers\Web\ProductPageController;
use Illuminate\Support\Facades\Route;

Route::get('/', HomeController::class)->name('home');

Route::view('login', 'auth.login')->name('login');
Route::view('register', 'auth.register')->name('register');

Route::get('verify-email/{id}/{hash}', VerifyEmailController::class)
    ->middleware(['signed', 'throttle:6,1'])
    ->name('verification.verify');

Route::view('profile', 'client-profile')->name('profile');
Route::view('cart', 'cart.index')->name('cart');
Route::view('orders', 'orders.index')->name('orders');
Route::view('favorites', 'favorites.index')->name('favorites');
Route::view('wallet', 'wallet.index')->name('wallet');

Route::prefix('admin')->name('admin.')->group(function () {
    Route::view('orders', 'admin.orders')->name('orders');
    Route::view('users', 'admin.users')->name('users');
});

Route::prefix('payments')->name('payments.')->group(function () {
    Route::view('{payment}/simulate', 'payments.simulate')->name('simulate');
});

Route::view('posts', 'blog.manage')->name('posts.index');
Route::get('posts/{post:slug}', PostPageController::class)->name('posts.show');

Route::view('products', 'products.index')->name('products.index');
Route::get('products/{product:slug}', ProductPageController::class)->name('products.show');
