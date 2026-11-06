<?php

use App\Models\Category;
use App\Models\Post;
use App\Models\Product;
use App\Models\User;
use App\Services\ContentCache;
use App\Services\PostService;
use App\Services\ProductService;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;

beforeEach(function () {
    Cache::flush();
    config()->set('content-cache.enabled', true);
    config()->set('content-cache.ttl', 3600);
});

test('product content is cached independently for every locale', function () {
    $cache = app(ContentCache::class);
    $calls = 0;
    $resolver = function () use (&$calls) {
        $calls++;

        return ['locale' => App::currentLocale(), 'calls' => $calls];
    };

    App::setLocale('en');
    expect($cache->rememberProducts('catalog', $resolver))->toBe(['locale' => 'en', 'calls' => 1]);
    expect($cache->rememberProducts('catalog', $resolver))->toBe(['locale' => 'en', 'calls' => 1]);

    App::setLocale('ru');
    expect($cache->rememberProducts('catalog', $resolver))->toBe(['locale' => 'ru', 'calls' => 2]);
    expect($calls)->toBe(2);
});

test('product search responses bypass shared cache', function () {
    $product = Product::factory()->create([
        'name' => 'Northern Reserve',
        'status' => 'active',
    ]);

    $this->getJson('/api/products?search=Northern%20Reserve')
        ->assertOk()
        ->assertJsonPath('data.0.id', $product->id);

    Product::query()->whereKey($product->id)->update(['name' => 'Southern Reserve']);

    $this->getJson('/api/products?search=Northern%20Reserve')
        ->assertOk()
        ->assertJsonCount(0, 'data');
});

test('equivalent product filters share one canonical cache key', function () {
    $firstCategory = Category::query()->create(['name' => 'Wine', 'slug' => 'wine']);
    $secondCategory = Category::query()->create(['name' => 'Tools', 'slug' => 'tools']);
    $product = Product::factory()->create([
        'name' => 'Original Name',
        'status' => 'active',
        'category_id' => $firstCategory->id,
    ]);

    $this->getJson("/api/products?categories[]={$firstCategory->id}&categories[]={$secondCategory->id}")
        ->assertOk()
        ->assertJsonPath('data.0.name', 'Original Name');

    Product::query()->whereKey($product->id)->update(['name' => 'Changed Name']);

    $this->getJson("/api/products?categories[]={$secondCategory->id}&categories[]={$firstCategory->id}")
        ->assertOk()
        ->assertJsonPath('data.0.name', 'Original Name');
});

test('catalog rejects page numbers that can create unreasonable cache cardinality', function () {
    $this->getJson('/api/products?page=501')
        ->assertUnprocessable()
        ->assertJsonValidationErrors('page');
});

test('requests with an untrusted host are rejected before reaching shared cache', function () {
    $this->withHeader('Host', 'attacker.example')
        ->getJson('/api/products')
        ->assertBadRequest();
});

test('domain invalidation replaces cached content without flushing other domains', function () {
    $cache = app(ContentCache::class);
    $productCalls = 0;
    $postCalls = 0;

    $products = function () use (&$productCalls) {
        return ['calls' => ++$productCalls];
    };
    $posts = function () use (&$postCalls) {
        return ['calls' => ++$postCalls];
    };

    expect($cache->rememberProducts('index', $products))->toBe(['calls' => 1]);
    expect($cache->rememberPosts('index', $posts))->toBe(['calls' => 1]);

    $cache->invalidateProducts();

    expect($cache->rememberProducts('index', $products))->toBe(['calls' => 2]);
    expect($cache->rememberPosts('index', $posts))->toBe(['calls' => 1]);
});

test('home content changes when either product or post content is invalidated', function () {
    $cache = app(ContentCache::class);
    $calls = 0;
    $home = function () use (&$calls) {
        return ['calls' => ++$calls];
    };

    expect($cache->rememberHome('landing', $home))->toBe(['calls' => 1]);
    expect($cache->rememberHome('landing', $home))->toBe(['calls' => 1]);

    $cache->invalidateProducts();
    expect($cache->rememberHome('landing', $home))->toBe(['calls' => 2]);

    $cache->invalidatePosts();
    expect($cache->rememberHome('landing', $home))->toBe(['calls' => 3]);
});

test('archiving a product invalidates the public catalogue cache', function () {
    $product = Product::factory()->create(['status' => 'active']);

    $this->getJson('/api/products')
        ->assertOk()
        ->assertJsonPath('data.0.id', $product->id);

    app(ProductService::class)->archive($product);

    $this->getJson('/api/products')
        ->assertOk()
        ->assertJsonCount(0, 'data');
});

test('deleting a post invalidates the public journal cache', function () {
    $author = User::factory()->create();
    $post = Post::factory()->for($author, 'author')->create(['status' => 'published']);

    $this->getJson('/api/posts')
        ->assertOk()
        ->assertJsonPath('data.0.id', $post->id);

    app(PostService::class)->delete($post);

    $this->getJson('/api/posts')
        ->assertOk()
        ->assertJsonCount(0, 'data');
});

test('public post authors do not expose private account data', function () {
    $author = User::factory()->create();
    $post = Post::factory()->for($author, 'author')->create(['status' => 'published']);

    $this->getJson("/api/posts/{$post->id}")
        ->assertOk()
        ->assertJsonMissingPath('data.author.email')
        ->assertJsonMissingPath('data.author.phone_number')
        ->assertJsonMissingPath('data.author.role');
});

test('content reads fall back to the resolver when cache is unavailable', function () {
    Cache::shouldReceive('rememberForever')
        ->once()
        ->andThrow(new RuntimeException('Cache unavailable'));

    expect(app(ContentCache::class)->rememberProducts('catalog', fn () => ['source' => 'database']))
        ->toBe(['source' => 'database']);
});

test('cache invalidation failures do not block successful writes', function () {
    Cache::shouldReceive('forever')
        ->once()
        ->andThrow(new RuntimeException('Cache unavailable'));

    expect(app(ContentCache::class)->invalidateProducts())->toBeFalse();
});

test('content cache recovery command rotates only the selected domain', function () {
    $cache = app(ContentCache::class);
    $productCalls = 0;
    $postCalls = 0;

    $products = function () use (&$productCalls) {
        return ['calls' => ++$productCalls];
    };
    $posts = function () use (&$postCalls) {
        return ['calls' => ++$postCalls];
    };

    expect($cache->rememberProducts('index', $products))->toBe(['calls' => 1]);
    expect($cache->rememberPosts('index', $posts))->toBe(['calls' => 1]);
    expect(Artisan::call('cache:content-invalidate', ['domain' => 'products']))->toBe(0);
    expect($cache->rememberProducts('index', $products))->toBe(['calls' => 2]);
    expect($cache->rememberPosts('index', $posts))->toBe(['calls' => 1]);
});
