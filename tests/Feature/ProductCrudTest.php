<?php

use App\Models\Attribute;
use App\Models\Category;
use App\Models\Product;
use App\Models\User;

function productToken(User $user): string
{
    return auth('api')->login($user);
}

function productAdmin(): User
{
    $admin = User::factory()->create();
    $admin->profile->forceFill(['role' => 'admin'])->save();

    return $admin;
}

function productPayload(Category $category, Attribute $attribute, array $overrides = []): array
{
    return [
        'name' => 'Northern Ice Wine',
        'description' => 'A limited wine from frozen northern grapes.',
        'status' => 'active',
        'category_id' => $category->id,
        'skus' => [[
            'sku' => 'WINE-ICE-500',
            'name' => '500 ml bottle',
            'stock_quantity' => 12,
            'price' => 3499,
            'currency' => 'EUR',
            'quantity_unit' => 'piece',
        ]],
        'attributes' => [[
            'attribute_id' => $attribute->id,
            'value' => '12.5',
        ]],
        ...$overrides,
    ];
}

test('product write routes require authentication', function () {
    $product = Product::factory()->create();

    $this->postJson('/api/products', [])->assertUnauthorized();
    $this->putJson("/api/products/{$product->id}", [])->assertUnauthorized();
    $this->deleteJson("/api/products/{$product->id}")->assertUnauthorized();
});

test('guests can read active products but not inactive products', function () {
    $active = Product::factory()->create(['status' => 'active']);
    $draft = Product::factory()->create(['status' => 'draft']);

    $this
        ->getJson('/api/products')
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.id', $active->id);

    $this->getJson("/api/products/{$active->id}")->assertOk();
    $this->getJson("/api/products/{$draft->id}")->assertForbidden();
});

test('only administrators can create products', function () {
    $category = Category::query()->create(['name' => 'Wine', 'slug' => 'wine']);
    $attribute = Attribute::query()->create(['name' => 'Alcohol', 'type' => 'decimal', 'unit' => '%']);
    $customer = User::factory()->create();

    $this
        ->withToken(productToken($customer))
        ->postJson('/api/products', productPayload($category, $attribute))
        ->assertForbidden();
});

test('only administrators can retrieve product form options', function () {
    $category = Category::query()->create(['name' => 'Wine', 'slug' => 'wine']);
    $attribute = Attribute::query()->create(['name' => 'Alcohol', 'type' => 'decimal', 'unit' => '%']);
    $customer = User::factory()->create();

    $this
        ->withToken(productToken($customer))
        ->getJson('/api/product-options')
        ->assertForbidden();

    $this
        ->withToken(productToken(productAdmin()))
        ->getJson('/api/product-options')
        ->assertOk()
        ->assertJsonPath('categories.0.id', $category->id)
        ->assertJsonPath('attributes.0.id', $attribute->id);
});

test('administrators can create products with skus and attributes', function () {
    $admin = productAdmin();
    $category = Category::query()->create(['name' => 'Wine', 'slug' => 'wine']);
    $attribute = Attribute::query()->create(['name' => 'Alcohol', 'type' => 'decimal', 'unit' => '%']);

    $response = $this
        ->withToken(productToken($admin))
        ->postJson('/api/products', productPayload($category, $attribute));

    $response
        ->assertCreated()
        ->assertJsonPath('data.name', 'Northern Ice Wine')
        ->assertJsonPath('data.slug', 'northern-ice-wine')
        ->assertJsonPath('data.category.id', $category->id)
        ->assertJsonPath('data.skus.0.sku', 'WINE-ICE-500')
        ->assertJsonPath('data.attributes.0.value', '12.5');

    $this->assertDatabaseHas('products', ['name' => 'Northern Ice Wine', 'category_id' => $category->id]);
    $this->assertDatabaseHas('skus', ['sku' => 'WINE-ICE-500', 'price' => 3499]);
    $this->assertDatabaseHas('attribute_values', ['attribute_id' => $attribute->id, 'value' => '12.5']);
});

test('product creation validates nested product data', function () {
    $admin = productAdmin();

    $this
        ->withToken(productToken($admin))
        ->postJson('/api/products', [
            'name' => '',
            'status' => 'unknown',
            'category_id' => 999999,
            'skus' => [[
                'sku' => '',
                'name' => '',
                'stock_quantity' => -1,
                'price' => -1,
                'currency' => 'euro',
                'quantity_unit' => 'litre',
            ]],
        ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors([
            'name',
            'status',
            'category_id',
            'skus.0.sku',
            'skus.0.name',
            'skus.0.stock_quantity',
            'skus.0.price',
            'skus.0.currency',
            'skus.0.quantity_unit',
        ]);
});

test('customers see active products while administrators see every product', function () {
    $active = Product::factory()->create(['status' => 'active']);
    $draft = Product::factory()->create(['status' => 'draft']);
    $customer = User::factory()->create();
    $admin = productAdmin();

    $this
        ->withToken(productToken($customer))
        ->getJson('/api/products')
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.id', $active->id);

    $this
        ->withToken(productToken($admin))
        ->getJson('/api/products')
        ->assertOk()
        ->assertJsonCount(2, 'data')
        ->assertJsonFragment(['id' => $draft->id]);
});

test('customers cannot view inactive products', function () {
    $product = Product::factory()->create(['status' => 'draft']);
    $customer = User::factory()->create();

    $this
        ->withToken(productToken($customer))
        ->getJson("/api/products/{$product->id}")
        ->assertForbidden();
});

test('administrators can replace product skus and attributes', function () {
    $admin = productAdmin();
    $category = Category::query()->create(['name' => 'Wine', 'slug' => 'wine']);
    $alcohol = Attribute::query()->create(['name' => 'Alcohol', 'type' => 'decimal', 'unit' => '%']);
    $vintage = Attribute::query()->create(['name' => 'Vintage', 'type' => 'integer']);
    $product = Product::factory()->create(['name' => 'Old Wine', 'slug' => 'old-wine']);
    $oldSku = $product->skus()->create([
        'sku' => 'OLD-750',
        'name' => 'Old bottle',
        'stock_quantity' => 2,
        'price' => 1000,
        'currency' => 'EUR',
        'quantity_unit' => 'piece',
    ]);
    $product->attributeValues()->create(['attribute_id' => $alcohol->id, 'value' => '10']);
    $payload = productPayload($category, $vintage, [
        'name' => 'New Wine',
        'skus' => [[
            'id' => $oldSku->id,
            'sku' => 'NEW-750',
            'name' => 'New bottle',
            'stock_quantity' => 5,
            'price' => 1999,
            'currency' => 'EUR',
            'quantity_unit' => 'piece',
        ]],
    ]);

    $this
        ->withToken(productToken($admin))
        ->putJson("/api/products/{$product->id}", $payload)
        ->assertOk()
        ->assertJsonPath('data.slug', 'new-wine')
        ->assertJsonPath('data.skus.0.id', $oldSku->id)
        ->assertJsonPath('data.attributes.0.attribute_id', $vintage->id);

    $this->assertDatabaseHas('skus', ['id' => $oldSku->id, 'sku' => 'NEW-750']);
    $this->assertDatabaseMissing('attribute_values', ['product_id' => $product->id, 'attribute_id' => $alcohol->id]);
});

test('administrators can delete products and their dependent data', function () {
    $admin = productAdmin();
    $product = Product::factory()->create();
    $sku = $product->skus()->create([
        'sku' => 'DELETE-750',
        'name' => 'Bottle',
        'stock_quantity' => 1,
        'price' => 1000,
        'currency' => 'EUR',
        'quantity_unit' => 'piece',
    ]);

    $this
        ->withToken(productToken($admin))
        ->deleteJson("/api/products/{$product->id}")
        ->assertNoContent();

    $this->assertDatabaseMissing('products', ['id' => $product->id]);
    $this->assertDatabaseMissing('skus', ['id' => $sku->id]);
});

test('product page can be opened by slug', function () {
    $product = Product::factory()->create();

    $this
        ->withoutVite()
        ->get("/products/{$product->slug}")
        ->assertOk()
        ->assertSee($product->name)
        ->assertSee('data-product-id="'.$product->id.'"', false);
});

test('unknown product slugs return not found', function () {
    $this->get('/products/missing-product')->assertNotFound();
});

test('catalog seeding creates product form options and a demonstration product', function () {
    $this->seed();

    $this->assertDatabaseHas('categories', ['slug' => 'wine']);
    $this->assertDatabaseHas('attributes', ['name' => 'Alcohol content']);
    $this->assertDatabaseHas('products', ['slug' => 'northern-ice-wine', 'status' => 'active']);
    $this->assertDatabaseHas('skus', ['sku' => 'ICE-WINE-375', 'price' => 3499]);
});
