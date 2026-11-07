<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Attribute;
use App\Models\Category;
use App\Models\Product;
use App\Services\ContentCache;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Gate;

class ProductOptionsController extends Controller
{
    public function __invoke(ContentCache $cache): JsonResponse
    {
        Gate::authorize('create', Product::class);
        $payload = $cache->rememberProducts('form-options', fn () => [
            'categories' => Category::query()->with('translations')->orderBy('name')->get()->map(fn (Category $category) => [
                'id' => $category->id,
                'name' => $category->localized('name'),
            ])->values()->all(),
            'attributes' => Attribute::query()->with('translations')->orderBy('name')->get()->map(fn (Attribute $attribute) => [
                'id' => $attribute->id,
                'name' => $attribute->localized('name'),
                'type' => $attribute->type,
                'unit' => $attribute->localized('unit'),
            ])->values()->all(),
        ]);

        return response()->json($payload);
    }
}
