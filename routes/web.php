<?php

use App\Models\Post;
use App\Models\Product;
use Illuminate\Support\Facades\Route;

Route::view('/', 'welcome');

Route::view('login', 'auth.login')->name('login');

Route::view('dashboard', 'dashboard')
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

Route::view('profile', 'profile')
    ->middleware(['auth'])
    ->name('profile');

require __DIR__.'/auth.php';

Route::view('posts', 'blog.manage')->name('posts.index');

Route::get('posts/{post:slug}', function (Post $post) {
    return view('blog.show', ['post' => $post]);
})->name('posts.show');

Route::view('products', 'products.index')->name('products.index');

Route::get('products/{product:slug}', function (Product $product) {
    return view('products.show', ['product' => $product]);
})->name('products.show');
