<?php

namespace Database\Seeders;

use App\Models\Attribute;
use App\Models\AttributeValue;
use App\Models\Category;
use App\Models\Post;
use App\Models\Product;
use App\Models\Sku;
use Illuminate\Database\Seeder;

class LocalizationSeeder extends Seeder
{
    public function run(): void
    {
        Category::query()->each(function (Category $category): void {
            $category->translations()->updateOrCreate(['locale' => 'en'], [
                'name' => $category->name,
                'slug' => $category->slug,
                'description' => $category->description,
            ]);
        });

        Attribute::query()->each(function (Attribute $attribute): void {
            $attribute->translations()->updateOrCreate(['locale' => 'en'], [
                'name' => $attribute->name,
                'unit' => $attribute->unit,
            ]);
        });

        AttributeValue::query()->each(fn (AttributeValue $value) => $value->translations()->updateOrCreate(
            ['locale' => 'en'],
            ['value' => $value->value],
        ));

        Product::query()->each(function (Product $product): void {
            $product->translations()->updateOrCreate(['locale' => 'en'], [
                'name' => $product->name,
                'slug' => $product->slug,
                'description' => $product->description,
            ]);
        });

        Sku::query()->each(fn (Sku $sku) => $sku->translations()->updateOrCreate(
            ['locale' => 'en'],
            ['name' => $sku->name],
        ));

        Post::query()->each(function (Post $post): void {
            $post->translations()->updateOrCreate(['locale' => 'en'], [
                'title' => $post->title,
                'slug' => $post->slug,
                'preview_text' => $post->preview_text,
                'body' => $post->body,
            ]);
        });

        $this->seedRussianCatalog();
    }

    private function seedRussianCatalog(): void
    {
        $categories = [
            'wine' => ['Вино', 'vino', 'Северные вина и лимитированные выпуски урожая.'],
            'grapes' => ['Виноград', 'vinograd', 'Свежий виноград и устойчивые сорта.'],
            'tools' => ['Инструменты', 'instrumenty', 'Оборудование для виноградника и погреба.'],
        ];
        $attributes = [
            'Alcohol content' => ['Содержание алкоголя', '%'],
            'Vintage' => ['Год урожая', null],
            'Origin' => ['Происхождение', null],
        ];
        $products = [
            'northern-ice-wine' => ['Северное ледяное вино', 'severnoe-ledyanoe-vino', 'Десертное вино небольшой партии из винограда, собранного после первых сильных морозов.'],
            'amur-grape-cuttings' => ['Саженцы амурского винограда', 'sazhentsy-amurskogo-vinograda', 'Укоренённые морозостойкие саженцы для северных садов и короткого сезона.'],
            'vineyard-pruner' => ['Секатор для виноградника', 'sekator-dlya-vinogradnika', 'Сбалансированный ручной секатор для точной сезонной работы с лозой и молодыми побегами.'],
            'northern-tasting-set' => ['Северный дегустационный набор', 'severnyy-degustatsionnyy-nabor', 'Подборка северных вин малых партий для спокойного вечера за столом.'],
        ];
        $skus = [
            'ICE-WINE-375' => 'Бутылка 375 мл',
            'ICE-WINE-750' => 'Бутылка 750 мл',
            'AMUR-CUTTING-3' => 'Набор из 3 штук',
            'PRUNER-STEEL' => 'Кованая сталь',
            'TASTING-SET-3' => 'Набор из трёх бутылок',
        ];

        foreach ($categories as $slug => [$name, $translatedSlug, $description]) {
            Category::query()->where('slug', $slug)->first()?->translations()->updateOrCreate(
                ['locale' => 'ru'],
                ['name' => $name, 'slug' => $translatedSlug, 'description' => $description],
            );
        }

        foreach ($attributes as $name => [$translatedName, $unit]) {
            Attribute::query()->where('name', $name)->first()?->translations()->updateOrCreate(
                ['locale' => 'ru'],
                ['name' => $translatedName, 'unit' => $unit],
            );
        }

        foreach ($products as $slug => [$name, $translatedSlug, $description]) {
            Product::query()->where('slug', $slug)->first()?->translations()->updateOrCreate(
                ['locale' => 'ru'],
                ['name' => $name, 'slug' => $translatedSlug, 'description' => $description],
            );
        }

        foreach ($skus as $sku => $name) {
            Sku::query()->where('sku', $sku)->first()?->translations()->updateOrCreate(
                ['locale' => 'ru'],
                ['name' => $name],
            );
        }

        AttributeValue::query()->each(fn (AttributeValue $value) => $value->translations()->updateOrCreate(
            ['locale' => 'ru'],
            ['value' => $value->value],
        ));
    }
}
