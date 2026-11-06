<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Attribute;
use App\Models\Category;
use App\Models\Sku;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProductFilterOptionsController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $canManage = $request->user('api')?->canManageContent() === true;
        $visibleProducts = fn (Builder $query) => $query->when(! $canManage, fn (Builder $query) => $query->where('status', 'active'));
        $prices = Sku::query()->whereHas('product', $visibleProducts);

        return response()->json([
            'categories' => Category::query()
                ->whereHas('products', $visibleProducts)
                ->with('translations')
                ->get()
                ->map(fn (Category $category) => [
                    'id' => $category->id,
                    'name' => $category->localized('name'),
                    'slug' => $category->localized('slug'),
                ])
                ->sortBy('name', SORT_NATURAL | SORT_FLAG_CASE)
                ->values(),
            'price' => [
                'min' => (int) (clone $prices)->min('price'),
                'max' => (int) (clone $prices)->max('price'),
                'currency' => 'EUR',
            ],
            'attributes' => Attribute::query()
                ->whereHas('values.product', $visibleProducts)
                ->with([
                    'translations',
                    'values' => fn ($query) => $query->whereHas('product', $visibleProducts)->with('translations')->orderBy('value'),
                ])
                ->get()
                ->map(fn (Attribute $attribute) => [
                    'id' => $attribute->id,
                    'name' => $attribute->localized('name'),
                    'unit' => $attribute->localized('unit'),
                    'values' => $attribute->values->unique('value')->map(fn ($value) => [
                        'value' => $value->value,
                        'label' => $value->localized('value'),
                    ])->values(),
                ])
                ->sortBy('name', SORT_NATURAL | SORT_FLAG_CASE)
                ->values(),
            'statuses' => $canManage ? ['draft', 'active', 'archived'] : [],
            'sorts' => ['newest', 'name_asc', 'price_asc', 'price_desc'],
        ]);
    }
}
