<?php

use App\Models\Product;
use App\Models\User;

test('favorite endpoints require a jwt', function () {
    $product = Product::factory()->create(['status' => 'active']);

    $this->getJson('/api/favorites')->assertUnauthorized();
    $this->postJson("/api/favorites/{$product->id}")->assertUnauthorized();
    $this->deleteJson("/api/favorites/{$product->id}")->assertUnauthorized();
});

test('customer can add list and remove favorite products', function () {
    $user = User::factory()->create();
    $product = Product::factory()->create(['status' => 'active']);
    $token = auth('api')->login($user);

    $this->withToken($token)->postJson("/api/favorites/{$product->id}")
        ->assertOk()
        ->assertJsonPath('data.id', $product->id)
        ->assertJsonPath('data.is_favorite', true);

    $this->withToken($token)->postJson("/api/favorites/{$product->id}")->assertOk();

    $this->assertDatabaseCount('favorites', 1);

    $this->withToken($token)->getJson('/api/favorites')
        ->assertOk()
        ->assertJsonPath('meta.total', 1)
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.id', $product->id)
        ->assertJsonPath('data.0.is_favorite', true);

    $this->withToken($token)->deleteJson("/api/favorites/{$product->id}")->assertNoContent();

    $this->assertDatabaseMissing('favorites', ['user_id' => $user->id, 'product_id' => $product->id]);
});

test('favorites belong only to the authenticated customer', function () {
    $product = Product::factory()->create(['status' => 'active']);
    $owner = User::factory()->create();
    $other = User::factory()->create();
    $owner->favoriteProducts()->attach($product);

    $this->withToken(auth('api')->login($other))->getJson('/api/favorites')
        ->assertOk()
        ->assertJsonCount(0, 'data');

    $this->withToken(auth('api')->login($other))->deleteJson("/api/favorites/{$product->id}")->assertNoContent();

    $this->assertDatabaseHas('favorites', ['user_id' => $owner->id, 'product_id' => $product->id]);
});

test('customer cannot add an unavailable product to favorites', function () {
    $product = Product::factory()->create(['status' => 'draft']);
    $token = auth('api')->login(User::factory()->create());

    $this->withToken($token)->postJson("/api/favorites/{$product->id}")->assertForbidden();
    $this->assertDatabaseCount('favorites', 0);
});

test('product responses include favorite state for the authenticated customer', function () {
    $product = Product::factory()->create(['status' => 'active']);
    $user = User::factory()->create();
    $user->favoriteProducts()->attach($product);

    $this->getJson("/api/products/{$product->id}")
        ->assertOk()
        ->assertJsonPath('data.is_favorite', false);

    $this->withToken(auth('api')->login($user))->getJson("/api/products/{$product->id}")
        ->assertOk()
        ->assertJsonPath('data.is_favorite', true);
});

test('favorites page renders the React mount', function () {
    $this->withoutVite()->get('/favorites')
        ->assertOk()
        ->assertSee('id="favorites-root"', false);
});
