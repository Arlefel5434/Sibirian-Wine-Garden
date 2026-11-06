<?php

namespace App\Services;

use App\Models\Attribute;
use App\Models\ProductTranslation;
use Illuminate\Database\Eloquent\Builder;

class ProductFilterService
{
    public function apply(Builder $query, array $filters, bool $canManage): Builder
    {
        $query
            ->when($filters['search'] ?? null, fn (Builder $query, string $search) => $query->where(function (Builder $query) use ($search) {
                $query->where('name', 'ilike', "%{$search}%")
                    ->orWhere('description', 'ilike', "%{$search}%")
                    ->orWhereHas('translations', fn (Builder $query) => $query
                        ->where('locale', app()->getLocale())
                        ->where(fn (Builder $query) => $query
                            ->where('name', 'ilike', "%{$search}%")
                            ->orWhere('description', 'ilike', "%{$search}%")));
            }))
            ->when($filters['categories'] ?? null, fn (Builder $query, array $categories) => $query->whereIn('category_id', $categories))
            ->when(isset($filters['price_from']) || isset($filters['price_to']), function (Builder $query) use ($filters) {
                $query->whereHas('skus', function (Builder $query) use ($filters) {
                    $query
                        ->when(isset($filters['price_from']), fn (Builder $query) => $query->where('price', '>=', $filters['price_from']))
                        ->when(isset($filters['price_to']), fn (Builder $query) => $query->where('price', '<=', $filters['price_to']));
                });
            })
            ->when($filters['in_stock'] ?? false, fn (Builder $query) => $query->whereHas('skus', fn (Builder $query) => $query->where('stock_quantity', '>', 0)));

        if ($canManage && isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        $attributeIds = Attribute::query()->whereKey(array_keys($filters['attributes'] ?? []))->pluck('id')->all();

        foreach ($attributeIds as $attributeId) {
            $values = array_values(array_filter($filters['attributes'][$attributeId] ?? []));

            if ($values) {
                $query->whereHas('attributeValues', fn (Builder $query) => $query->where('attribute_id', $attributeId)->whereIn('value', $values));
            }
        }

        $query->withMin('skus', 'price');

        return match ($filters['sort'] ?? 'newest') {
            'name_asc' => $query
                ->orderBy(ProductTranslation::query()
                    ->select('name')
                    ->whereColumn('product_id', 'products.id')
                    ->where('locale', app()->getLocale())
                    ->limit(1))
                ->orderBy('name'),
            'price_asc' => $query->orderBy('skus_min_price'),
            'price_desc' => $query->orderByDesc('skus_min_price'),
            default => $query->latest(),
        };
    }
}
