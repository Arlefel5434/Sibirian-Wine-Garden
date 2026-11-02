<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Product\StoreProductRequest;
use App\Http\Requests\Product\UpdateProductRequest;
use App\Http\Resources\ProductResource;
use App\Models\Product;
use App\Services\ProductService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Gate;
use Symfony\Component\HttpFoundation\Response;

class ProductController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        Gate::authorize('viewAny', Product::class);

        $products = Product::query()
            ->with(['category', 'skus', 'attributeValues.attribute'])
            ->when(! $request->user()->isAdmin(), fn ($query) => $query->where('status', 'active'))
            ->latest()
            ->paginate(15);

        return ProductResource::collection($products);
    }

    public function store(StoreProductRequest $request, ProductService $products): JsonResponse
    {
        $product = $products->create($request->validated());

        return ProductResource::make($this->loadRelations($product))
            ->response()
            ->setStatusCode(Response::HTTP_CREATED);
    }

    public function show(Product $product): ProductResource
    {
        Gate::authorize('view', $product);

        return ProductResource::make($this->loadRelations($product));
    }

    public function update(
        UpdateProductRequest $request,
        Product $product,
        ProductService $products,
    ): ProductResource {
        return ProductResource::make(
            $this->loadRelations($products->update($product, $request->validated())),
        );
    }

    public function destroy(Product $product, ProductService $products): Response
    {
        Gate::authorize('delete', $product);
        $products->delete($product);

        return response()->noContent();
    }

    private function loadRelations(Product $product): Product
    {
        return $product->load(['category', 'skus', 'attributeValues.attribute']);
    }
}
