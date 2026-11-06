<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Attribute;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Gate;

class ProductOptionsController extends Controller
{
    public function __invoke(): JsonResponse
    {
        Gate::authorize('create', Product::class);

        return response()->json([
            'categories' => Category::query()->with('translations')->orderBy('name')->get()->map(fn (Category $category) => [
                'id' => $category->id,
                'name' => $category->localized('name'),
            ]),
            'attributes' => Attribute::query()->with('translations')->orderBy('name')->get()->map(fn (Attribute $attribute) => [
                'id' => $attribute->id,
                'name' => $attribute->localized('name'),
                'type' => $attribute->type,
                'unit' => $attribute->localized('unit'),
            ]),
        ]);
    }
}
