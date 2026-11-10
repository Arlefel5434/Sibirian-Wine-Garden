<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Post\StorePostRequest;
use App\Http\Requests\Post\UpdatePostRequest;
use App\Http\Resources\PostResource;
use App\Models\Post;
use App\Services\ContentCache;
use App\Services\PostService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Symfony\Component\HttpFoundation\Response;

class PostController extends Controller
{
    public function index(Request $request, ContentCache $cache): JsonResponse
    {
        $user = $request->user('api');
        $canManage = $user?->canManageContent() === true;
        $variant = 'index:'.$cache->fingerprint([
            'page' => $request->integer('page', 1),
            'scope' => $canManage ? 'manage' : 'public',
        ]);

        $payload = $cache->rememberPosts($variant, function () use ($canManage) {
            $posts = Post::query()
                ->with(['author.profile', 'translations'])
                ->when(! $canManage, fn ($query) => $query->where('status', 'published'))
                ->latest()
                ->paginate(9);

            return PostResource::collection($posts)->response()->getData(true);
        }, ! $canManage);

        return response()->json($payload);
    }

    public function store(StorePostRequest $request, PostService $posts): JsonResponse
    {
        $post = $posts->create($request->user(), $request->validated());

        return PostResource::make($post->load(['author.profile', 'translations']))
            ->response()
            ->setStatusCode(Response::HTTP_CREATED);
    }

    public function show(Request $request, Post $post, ContentCache $cache): JsonResponse
    {
        $user = $request->user('api');
        Gate::forUser($user)->authorize('view', $post);
        $canManage = $user?->canManageContent() === true;
        $payload = $cache->rememberPosts(
            "show:{$post->id}",
            fn () => PostResource::make($post->load(['author.profile', 'translations']))->response()->getData(true),
            ! $canManage && $post->status === 'published',
        );

        return response()->json($payload);
    }

    public function update(
        UpdatePostRequest $request,
        Post $post,
        PostService $posts,
    ): PostResource {
        return PostResource::make(
            $posts->update($post, $request->validated())->load(['author.profile', 'translations']),
        );
    }

    public function destroy(Post $post, PostService $posts): Response
    {
        Gate::authorize('delete', $post);
        $posts->delete($post);

        return response()->noContent();
    }
}
