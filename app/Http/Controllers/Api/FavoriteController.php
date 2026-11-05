<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\ProductResource;
use App\Models\Product;
use App\Services\FavoriteService;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Gate;
use Symfony\Component\HttpFoundation\Response;

class FavoriteController extends Controller
{
    public function index(Request $request, FavoriteService $favorites): AnonymousResourceCollection
    {
        return ProductResource::collection($favorites->list($request->user('api')));
    }

    public function store(Request $request, Product $product, FavoriteService $favorites): ProductResource
    {
        Gate::forUser($request->user('api'))->authorize('view', $product);

        return ProductResource::make($favorites->add($request->user('api'), $product));
    }

    public function destroy(Request $request, Product $product, FavoriteService $favorites): Response
    {
        $favorites->remove($request->user('api'), $product);

        return response()->noContent();
    }
}
