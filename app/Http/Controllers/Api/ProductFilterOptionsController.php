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
                ->orderBy('name')
                ->get(['id', 'name', 'slug']),
            'price' => [
                'min' => (int) (clone $prices)->min('price'),
                'max' => (int) (clone $prices)->max('price'),
                'currency' => 'EUR',
            ],
            'attributes' => Attribute::query()
                ->whereHas('values.product', $visibleProducts)
                ->with(['values' => fn ($query) => $query->whereHas('product', $visibleProducts)->select('attribute_id', 'value')->distinct()->orderBy('value')])
                ->orderBy('name')
                ->get()
                ->map(fn (Attribute $attribute) => [
                    'id' => $attribute->id,
                    'name' => $attribute->name,
                    'unit' => $attribute->unit,
                    'values' => $attribute->values->pluck('value')->values(),
                ]),
            'statuses' => $canManage ? ['draft', 'active', 'archived'] : [],
            'sorts' => ['newest', 'name_asc', 'price_asc', 'price_desc'],
        ]);
    }
}
