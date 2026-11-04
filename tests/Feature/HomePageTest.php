<?php

use App\Models\Post;
use App\Models\Product;

test('home page renders the React application mount', function () {
    $this
        ->withoutVite()
        ->get('/')
        ->assertOk()
        ->assertSee('id="home-root"', false)
        ->assertSee('Siberian Wine Garden');
});

test('home API shows published stories and active products', function () {
    $published = Post::factory()->create(['status' => 'published']);
    $draft = Post::factory()->create(['status' => 'draft']);
    $active = Product::factory()->create(['status' => 'active']);
    $archived = Product::factory()->create(['status' => 'archived']);

    $this
        ->getJson('/api/home')
        ->assertOk()
        ->assertJsonFragment(['title' => $published->title])
        ->assertJsonMissing(['title' => $draft->title])
        ->assertJsonFragment(['name' => $active->name])
        ->assertJsonMissing(['name' => $archived->name]);
});

test('home page limits product and story previews', function () {
    Post::factory()->count(5)->create(['status' => 'published']);
    Product::factory()->count(6)->create(['status' => 'active']);

    $this
        ->getJson('/api/home')
        ->assertOk()
        ->assertJsonCount(3, 'posts')
        ->assertJsonCount(4, 'products');
});
