<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\PostResource;
use App\Http\Resources\ProductResource;
use App\Models\Post;
use App\Models\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class HomeController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $posts = Post::query()
            ->with(['author.profile', 'translations'])
            ->where('status', 'published')
            ->latest()
            ->limit(3)
            ->get();
        $products = Product::query()
            ->with([
                'translations',
                'category.translations',
                'skus.translations',
                'attributeValues.translations',
                'attributeValues.attribute.translations',
            ])
            ->where('status', 'active')
            ->latest()
            ->limit(4)
            ->get();

        return response()->json([
            'posts' => PostResource::collection($posts)->resolve($request),
            'products' => ProductResource::collection($products)->resolve($request),
        ]);
    }
}
