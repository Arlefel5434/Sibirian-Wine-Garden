<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Product\StoreProductRequest;
use App\Http\Requests\Product\UpdateProductRequest;
use App\Http\Requests\ProductFilterRequest;
use App\Http\Resources\ProductResource;
use App\Models\Product;
use App\Models\User;
use App\Services\ContentCache;
use App\Services\ProductFilterService;
use App\Services\ProductService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Symfony\Component\HttpFoundation\Response;

class ProductController extends Controller
{
    public function index(
        ProductFilterRequest $request,
        ProductFilterService $filters,
        ContentCache $cache,
    ): JsonResponse {
        $user = $request->user('api');
        Gate::forUser($user)->authorize('viewAny', Product::class);
        $validated = $this->normalizeFilters($request->validated());
        $variant = 'index:'.$cache->fingerprint([
            'filters' => $validated,
        ]);

        $payload = $cache->rememberProducts($variant, function () use ($filters, $user, $validated) {
            $query = Product::query()
                ->with($this->relations())
                ->when($user, fn ($query) => $query->withExists(['favoritedBy as is_favorite' => fn ($favorites) => $favorites->where('users.id', $user->id)]))
                ->when(! $user?->canManageContent(), fn ($query) => $query->where('status', 'active'));

            $products = $filters->apply($query, $validated, $user?->canManageContent() === true)
                ->paginate(15)
                ->appends($validated);

            return ProductResource::collection($products)->response()->getData(true);
        }, $user === null && ! $request->filled('search'));

        return response()->json($payload);
    }

    public function store(StoreProductRequest $request, ProductService $products): JsonResponse
    {
        $product = $products->create($request->validated());

        return ProductResource::make($this->loadRelations($product))
            ->response()
            ->setStatusCode(Response::HTTP_CREATED);
    }

    public function show(Request $request, Product $product, ContentCache $cache): JsonResponse
    {
        $user = $request->user('api');
        Gate::forUser($user)->authorize('view', $product);
        $payload = $cache->rememberProducts(
            "show:{$product->id}",
            fn () => ProductResource::make($this->loadRelations($product, $user))->response()->getData(true),
            $user === null,
        );

        return response()->json($payload);
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
        $product->load($this->relations());

        if ($user) {
            $product->loadExists(['favoritedBy as is_favorite' => fn ($favorites) => $favorites->where('users.id', $user->id)]);
        }

        return $product;
    }

    private function relations(): array
    {
        return [
            'translations',
            'category.translations',
            'skus.translations',
            'attributeValues.translations',
            'attributeValues.attribute.translations',
        ];
    }

    private function normalizeFilters(array $filters): array
    {
        if (isset($filters['categories'])) {
            sort($filters['categories'], SORT_NUMERIC);
            $filters['categories'] = array_values($filters['categories']);
        }

        if (isset($filters['attributes'])) {
            ksort($filters['attributes'], SORT_NATURAL);

            foreach ($filters['attributes'] as &$values) {
                sort($values, SORT_NATURAL | SORT_FLAG_CASE);
                $values = array_values($values);
            }

            unset($values);
        }

        ksort($filters, SORT_NATURAL);

        return $filters;
    }
}
