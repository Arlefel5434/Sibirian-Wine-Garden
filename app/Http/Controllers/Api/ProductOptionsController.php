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
            'categories' => Category::query()->orderBy('name')->get(['id', 'name']),
            'attributes' => Attribute::query()->orderBy('name')->get(['id', 'name', 'type', 'unit']),
        ]);
    }
}
