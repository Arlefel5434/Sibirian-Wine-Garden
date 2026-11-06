<?php

namespace App\Services;

use App\Models\Product;
use App\Models\ProductTranslation;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ProductService
{
    public function __construct(private readonly ContentCache $cache) {}

    public function create(array $data): Product
    {
        $product = DB::transaction(function () use ($data) {
            $skus = $data['skus'];
            $attributes = $data['attributes'] ?? [];
            $translations = $data['translations'] ?? [];
            unset($data['skus'], $data['attributes'], $data['translations']);

            $product = Product::query()->create([
                ...$data,
                'slug' => $this->uniqueSlug($data['name']),
            ]);

            $product->skus()->createMany($skus);
            $product->attributeValues()->createMany($attributes);
            $this->syncTranslations($product, $translations);

            return $product;
        });

        $this->cache->invalidateProducts();

        return $product;
    }

    public function update(Product $product, array $data): Product
    {
        $product = DB::transaction(function () use ($product, $data) {
            $skus = $data['skus'];
            $attributes = $data['attributes'] ?? [];
            $translations = $data['translations'] ?? [];
            unset($data['skus'], $data['attributes'], $data['translations']);

            if ($product->name !== $data['name']) {
                $data['slug'] = $this->uniqueSlug($data['name'], $product);
            }

            $product->update($data);
            $keptSkuIds = [];

            foreach ($skus as $skuData) {
                $sku = isset($skuData['id'])
                    ? $product->skus()->findOrFail($skuData['id'])
                    : $product->skus()->make();
                unset($skuData['id']);
                $sku->fill($skuData)->save();
                $keptSkuIds[] = $sku->id;
            }

            $product->skus()->whereNotIn('id', $keptSkuIds)->delete();
            $attributeIds = [];

            foreach ($attributes as $attributeData) {
                $product->attributeValues()->updateOrCreate(
                    ['attribute_id' => $attributeData['attribute_id']],
                    ['value' => $attributeData['value']],
                );
                $attributeIds[] = $attributeData['attribute_id'];
            }

            $product->attributeValues()->whereNotIn('attribute_id', $attributeIds)->delete();
            $this->syncTranslations($product, $translations);

            return $product;
        });

        $this->cache->invalidateProducts();

        return $product;
    }

    public function archive(Product $product): void
    {
        if ($product->status === 'archived') {
            return;
        }

        $product->update(['status' => 'archived']);
        $this->cache->invalidateProducts();
    }

    private function uniqueSlug(string $name, ?Product $ignoredProduct = null): string
    {
        $base = Str::slug($name) ?: 'product';
        $slug = $base;
        $suffix = 2;

        while (Product::query()
            ->where('slug', $slug)
            ->when($ignoredProduct, fn ($query) => $query->whereKeyNot($ignoredProduct->getKey()))
            ->exists()) {
            $slug = $base.'-'.$suffix;
            $suffix++;
        }

        return $slug;
    }

    private function syncTranslations(Product $product, array $translations): void
    {
        $translations = collect($translations)
            ->keyBy('locale')
            ->put(config('localization.default'), [
                'locale' => config('localization.default'),
                'name' => $product->name,
                'description' => $product->description,
            ]);

        foreach ($translations as $translation) {
            $locale = $translation['locale'];
            $existing = $product->translations()->where('locale', $locale)->first();
            $product->translations()->updateOrCreate(
                ['locale' => $locale],
                [
                    'name' => $translation['name'],
                    'slug' => $this->uniqueTranslationSlug($translation['name'], $locale, $existing),
                    'description' => $translation['description'] ?? null,
                ],
            );
        }
    }

    private function uniqueTranslationSlug(string $name, string $locale, ?ProductTranslation $ignored = null): string
    {
        $base = Str::slug($name) ?: 'product';
        $slug = $base;
        $suffix = 2;

        while (ProductTranslation::query()
            ->where('locale', $locale)
            ->where('slug', $slug)
            ->when($ignored, fn ($query) => $query->whereKeyNot($ignored->getKey()))
            ->exists()) {
            $slug = $base.'-'.$suffix;
            $suffix++;
        }

        return $slug;
    }
}
