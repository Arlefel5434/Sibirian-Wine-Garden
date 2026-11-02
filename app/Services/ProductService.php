<?php

namespace App\Services;

use App\Models\Product;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ProductService
{
    public function create(array $data): Product
    {
        return DB::transaction(function () use ($data) {
            $skus = $data['skus'];
            $attributes = $data['attributes'] ?? [];
            unset($data['skus'], $data['attributes']);

            $product = Product::query()->create([
                ...$data,
                'slug' => $this->uniqueSlug($data['name']),
            ]);

            $product->skus()->createMany($skus);
            $product->attributeValues()->createMany($attributes);

            return $product;
        });
    }

    public function update(Product $product, array $data): Product
    {
        return DB::transaction(function () use ($product, $data) {
            $skus = $data['skus'];
            $attributes = $data['attributes'] ?? [];
            unset($data['skus'], $data['attributes']);

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

            return $product;
        });
    }

    public function delete(Product $product): void
    {
        $product->delete();
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
}
