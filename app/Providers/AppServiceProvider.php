<?php

namespace App\Providers;

use App\Models\Attribute;
use App\Models\AttributeTranslation;
use App\Models\AttributeValue;
use App\Models\AttributeValueTranslation;
use App\Models\Category;
use App\Models\CategoryTranslation;
use App\Models\Post;
use App\Models\PostTranslation;
use App\Models\Product;
use App\Models\ProductTranslation;
use App\Models\Profile;
use App\Models\Sku;
use App\Models\SkuTranslation;
use App\Models\User;
use App\Observers\InvalidatePostCache;
use App\Observers\InvalidateProductCache;
use Illuminate\Contracts\Config\Repository;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $config = $this->app->make(Repository::class);

        $config->set('auth.guards', [
            'api' => $config->get('auth.guards.api'),
        ]);
        $config->set('auth.passwords', []);
        $config->set('auth.password_timeout', null);
    }

    public function boot(): void
    {
        foreach ([
            Product::class,
            ProductTranslation::class,
            Category::class,
            CategoryTranslation::class,
            Attribute::class,
            AttributeTranslation::class,
            AttributeValue::class,
            AttributeValueTranslation::class,
            Sku::class,
            SkuTranslation::class,
        ] as $model) {
            $model::observe(InvalidateProductCache::class);
        }

        foreach ([Post::class, PostTranslation::class, User::class, Profile::class] as $model) {
            $model::observe(InvalidatePostCache::class);
        }
    }
}
