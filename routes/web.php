<?php

use App\Http\Controllers\HomeController;
use App\Models\Post;
use App\Models\Product;
use Illuminate\Support\Facades\Route;

Route::get('/', HomeController::class)->name('home');

Route::view('login', 'auth.login')->name('login');

Route::view('dashboard', 'dashboard')
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

Route::view('profile', 'client-profile')
    ->name('profile');

Route::view('cart', 'cart.index')->name('cart');
Route::view('orders', 'orders.index')->name('orders');
Route::view('favorites', 'favorites.index')->name('favorites');
Route::view('wallet', 'wallet.index')->name('wallet');
Route::view('admin/orders', 'admin.orders')->name('admin.orders');
Route::view('admin/users', 'admin.users')->name('admin.users');
Route::view('payments/{payment}/simulate', 'payments.simulate')->name('payments.simulate');

require __DIR__.'/auth.php';

Route::view('posts', 'blog.manage')->name('posts.index');

Route::get('posts/{post:slug}', function (Post $post) {
    return view('blog.show', ['post' => $post]);
})->name('posts.show');

Route::view('products', 'products.index')->name('products.index');

Route::get('products/{product:slug}', function (Product $product) {
    return view('products.show', ['product' => $product]);
})->name('products.show');
