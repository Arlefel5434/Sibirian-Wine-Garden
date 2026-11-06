<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Product\StoreProductRequest;
use App\Http\Requests\Product\UpdateProductRequest;
use App\Http\Requests\ProductFilterRequest;
use App\Http\Resources\ProductResource;
use App\Models\Product;
use App\Models\User;
use App\Services\ProductFilterService;
use App\Services\ProductService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Gate;
use Symfony\Component\HttpFoundation\Response;

class ProductController extends Controller
{
    public function index(ProductFilterRequest $request, ProductFilterService $filters): AnonymousResourceCollection
    {
        $user = $request->user('api');
        Gate::forUser($user)->authorize('viewAny', Product::class);

        $query = Product::query()
            ->with(['category', 'skus', 'attributeValues.attribute'])
            ->when($user, fn ($query) => $query->withExists(['favoritedBy as is_favorite' => fn ($favorites) => $favorites->where('users.id', $user->id)]))
            ->when(! $user?->canManageContent(), fn ($query) => $query->where('status', 'active'));

        $products = $filters->apply($query, $request->validated(), $user?->canManageContent() === true)->paginate(15)->withQueryString();

        return ProductResource::collection($products);
    }

    public function store(StoreProductRequest $request, ProductService $products): JsonResponse
    {
        $product = $products->create($request->validated());

        return ProductResource::make($this->loadRelations($product))
            ->response()
            ->setStatusCode(Response::HTTP_CREATED);
    }

    public function show(Request $request, Product $product): ProductResource
    {
        $user = $request->user('api');
        Gate::forUser($user)->authorize('view', $product);

        return ProductResource::make($this->loadRelations($product, $user));
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
        $products->archive($product);

        return response()->noContent();
    }

    private function loadRelations(Product $product, ?User $user = null): Product
    {
        $product->load(['category', 'skus', 'attributeValues.attribute']);

        if ($user) {
            $product->loadExists(['favoritedBy as is_favorite' => fn ($favorites) => $favorites->where('users.id', $user->id)]);
        }

        return $product;
    }
}
