<?php

use App\Models\Attribute;
use App\Models\Category;
use App\Models\Post;
use App\Models\Product;

test('english is the default locale and a supported query locale is remembered', function () {
    $this->get('/')->assertOk()->assertHeader('Content-Language', 'en');

    $this->get('/?lang=ru')
        ->assertOk()
        ->assertHeader('Content-Language', 'ru')
        ->assertPlainCookie('locale', 'ru')
        ->assertSee('<html lang="ru">', false);
});

test('supported locales are exposed from application configuration', function () {
    $this->getJson('/api/locales')
        ->assertOk()
        ->assertJsonPath('current', 'en')
        ->assertJsonPath('supported.0.code', 'en')
        ->assertJsonPath('supported.1.code', 'ru');
});

test('product content and filter labels are localized without changing canonical filter values', function () {
    $category = Category::query()->create(['name' => 'Wine', 'slug' => 'wine']);
    $category->translations()->create(['locale' => 'ru', 'name' => 'Вино', 'slug' => 'vino']);
    $attribute = Attribute::query()->create(['name' => 'Origin', 'type' => 'string']);
    $attribute->translations()->create(['locale' => 'ru', 'name' => 'Происхождение']);
    $product = Product::factory()->create([
        'name' => 'Northern Wine',
        'slug' => 'northern-wine',
        'description' => 'English description',
        'category_id' => $category->id,
        'status' => 'active',
    ]);
    $product->translations()->create([
        'locale' => 'ru',
        'name' => 'Северное вино',
        'slug' => 'severnoe-vino',
        'description' => 'Описание на русском',
    ]);
    $value = $product->attributeValues()->create(['attribute_id' => $attribute->id, 'value' => 'Siberia']);
    $value->translations()->create(['locale' => 'ru', 'value' => 'Сибирь']);
    $product->skus()->create([
        'sku' => 'LOCAL-WINE',
        'name' => 'Bottle',
        'stock_quantity' => 1,
        'price' => 1000,
        'currency' => 'EUR',
        'quantity_unit' => 'piece',
    ]);

    $this->withHeader('X-Locale', 'ru')->getJson("/api/products/{$product->id}")
        ->assertOk()
        ->assertJsonPath('data.name', 'Северное вино')
        ->assertJsonPath('data.category.name', 'Вино')
        ->assertJsonPath('data.attributes.0.name', 'Происхождение')
        ->assertJsonPath('data.attributes.0.value', 'Сибирь')
        ->assertJsonPath('data.attributes.0.canonical_value', 'Siberia');

    $this->withHeader('X-Locale', 'ru')->getJson('/api/product-filters')
        ->assertOk()
        ->assertJsonPath('attributes.0.values.0.value', 'Siberia')
        ->assertJsonPath('attributes.0.values.0.label', 'Сибирь');

    $this->withHeader('X-Locale', 'ru')->get('/products/severnoe-vino')->assertOk();
});

test('missing translations fall back to base english content', function () {
    $product = Product::factory()->create([
        'name' => 'Fallback wine',
        'slug' => 'fallback-wine',
        'description' => 'Fallback description',
        'status' => 'active',
    ]);

    $this->withHeader('X-Locale', 'ru')->getJson("/api/products/{$product->id}")
        ->assertOk()
        ->assertJsonPath('data.name', 'Fallback wine')
        ->assertJsonPath('data.description', 'Fallback description');
});

test('post content uses the requested translation', function () {
    $post = Post::factory()->create(['status' => 'published']);
    $post->translations()->create([
        'locale' => 'ru',
        'title' => 'Новости сада',
        'slug' => 'novosti-sada',
        'preview_text' => 'Короткое описание',
        'body' => [['type' => 'paragraph', 'data' => ['text' => 'Русский текст']]],
    ]);

    $this->withHeader('X-Locale', 'ru')->getJson("/api/posts/{$post->id}")
        ->assertOk()
        ->assertJsonPath('data.title', 'Новости сада')
        ->assertJsonPath('data.body.0.data.text', 'Русский текст');

    $this->withHeader('X-Locale', 'ru')->get('/posts/novosti-sada')->assertOk();
});
