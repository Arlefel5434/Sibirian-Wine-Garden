<?php

use App\Models\Product;
use App\Models\User;

function cartProduct(int $stock = 5, int $price = 3499): array
{
    $product = Product::factory()->create(['status' => 'active']);
    $sku = $product->skus()->create(['sku' => fake()->unique()->bothify('CART-####'), 'name' => 'Bottle', 'stock_quantity' => $stock, 'price' => $price, 'currency' => 'EUR', 'quantity_unit' => 'piece']);

    return [$product, $sku];
}

test('cart endpoints require a jwt', function () {
    $this->getJson('/api/cart')->assertUnauthorized();
    $this->postJson('/api/cart/items', [])->assertUnauthorized();
    $this->postJson('/api/checkout', [])->assertUnauthorized();
});

test('customer can add and update an available sku', function () {
    [, $sku] = cartProduct();
    $user = User::factory()->create();
    $token = auth('api')->login($user);

    $response = $this->withToken($token)->postJson('/api/cart/items', ['sku_id' => $sku->id, 'quantity' => 2]);
    $itemId = $response->json('data.items.0.id');

    $response->assertOk()->assertJsonPath('data.items_count', 1)->assertJsonPath('data.total_amount', 6998);
    $this->withToken($token)->patchJson("/api/cart/items/{$itemId}", ['quantity' => 3])->assertOk()->assertJsonPath('data.items_count', 1)->assertJsonPath('data.total_amount', 10497);
});

test('cart counter represents distinct positions instead of total units', function () {
    [, $firstSku] = cartProduct(stock: 10);
    [, $secondSku] = cartProduct(stock: 10);
    $user = User::factory()->create();
    $token = auth('api')->login($user);

    $firstResponse = $this->withToken($token)->postJson('/api/cart/items', ['sku_id' => $firstSku->id, 'quantity' => 4]);
    $firstItemId = $firstResponse->json('data.items.0.id');

    $firstResponse->assertOk()
        ->assertJsonPath('data.items_count', 1)
        ->assertJsonPath('data.items.0.quantity', 4);

    $this->withToken($token)->postJson('/api/cart/items', ['sku_id' => $firstSku->id, 'quantity' => 2])
        ->assertOk()
        ->assertJsonPath('data.items_count', 1)
        ->assertJsonPath('data.items.0.quantity', 6);

    $this->withToken($token)->postJson('/api/cart/items', ['sku_id' => $secondSku->id, 'quantity' => 3])
        ->assertOk()
        ->assertJsonPath('data.items_count', 2)
        ->assertJsonCount(2, 'data.items');

    $this->withToken($token)->deleteJson("/api/cart/items/{$firstItemId}")
        ->assertOk()
        ->assertJsonPath('data.items_count', 1)
        ->assertJsonCount(1, 'data.items');

    $this->withToken($token)->getJson('/api/cart')
        ->assertOk()
        ->assertJsonPath('data.items_count', 1)
        ->assertJsonPath('data.items.0.sku.id', $secondSku->id);
});

test('cart validates quantity boundaries', function () {
    [, $sku] = cartProduct(stock: 200);
    $token = auth('api')->login(User::factory()->create());

    $this->withToken($token)->postJson('/api/cart/items', ['sku_id' => $sku->id, 'quantity' => 0])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('quantity');

    $this->withToken($token)->postJson('/api/cart/items', ['sku_id' => $sku->id, 'quantity' => 100])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('quantity');
});

test('customer cannot change another customers cart item', function () {
    [, $sku] = cartProduct();
    $owner = User::factory()->create();
    $other = User::factory()->create();
    $item = $owner->cart()->create()->items()->create(['sku_id' => $sku->id, 'quantity' => 1]);

    $this->withToken(auth('api')->login($other))->patchJson("/api/cart/items/{$item->id}", ['quantity' => 2])->assertNotFound();
});

test('cart rejects quantities above current stock', function () {
    [, $sku] = cartProduct(stock: 2);
    $user = User::factory()->create();

    $this->withToken(auth('api')->login($user))->postJson('/api/cart/items', ['sku_id' => $sku->id, 'quantity' => 3])->assertUnprocessable()->assertJsonValidationErrors('quantity');
});

test('checkout rejects an empty cart', function () {
    $token = auth('api')->login(User::factory()->create());

    $this->withToken($token)->postJson('/api/checkout', ['recipient_name' => 'Test Customer', 'country_code' => 'DE', 'city' => 'Berlin', 'postal_code' => '10115', 'address_line' => 'Garden street 1'])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('cart');
});

test('checkout uses database prices and atomically creates order snapshots', function () {
    [$product, $sku] = cartProduct(stock: 5, price: 3499);
    $user = User::factory()->create();
    $token = auth('api')->login($user);
    $this->withToken($token)->postJson('/api/cart/items', ['sku_id' => $sku->id, 'quantity' => 2, 'price' => 1]);

    $this->withToken($token)->postJson('/api/checkout', ['recipient_name' => 'Test Customer', 'country_code' => 'DE', 'city' => 'Berlin', 'postal_code' => '10115', 'address_line' => 'Garden street 1'])
        ->assertCreated()
        ->assertJsonPath('data.total_amount', 6998)
        ->assertJsonPath('data.items.0.product_name', $product->name)
        ->assertJsonPath('data.items.0.price', 3499);

    expect($sku->refresh()->stock_quantity)->toBe(3)
        ->and($user->cart->items()->count())->toBe(0)
        ->and($user->orders()->count())->toBe(1);
});
