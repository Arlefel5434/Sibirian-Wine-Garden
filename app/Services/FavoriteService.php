<?php

namespace App\Services;

use App\Models\Product;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class FavoriteService
{
    public function list(User $user): LengthAwarePaginator
    {
        return $user->favoriteProducts()
            ->where('products.status', 'active')
            ->with([
                'translations',
                'category.translations',
                'skus.translations',
                'attributeValues.translations',
                'attributeValues.attribute.translations',
            ])
            ->withExists(['favoritedBy as is_favorite' => fn ($favorites) => $favorites->where('users.id', $user->id)])
            ->orderByPivot('created_at', 'desc')
            ->paginate(15);
    }

    public function add(User $user, Product $product): Product
    {
        $user->favoriteProducts()->syncWithoutDetaching([$product->id]);

        return $product
            ->load([
                'translations',
                'category.translations',
                'skus.translations',
                'attributeValues.translations',
                'attributeValues.attribute.translations',
            ])
            ->setAttribute('is_favorite', true);
    }

    public function remove(User $user, Product $product): void
    {
        $user->favoriteProducts()->detach($product->id);
    }
}
