<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Post\StorePostRequest;
use App\Http\Requests\Post\UpdatePostRequest;
use App\Http\Resources\PostResource;
use App\Models\Post;
use App\Services\PostService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Gate;
use Symfony\Component\HttpFoundation\Response;

class PostController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $user = $request->user('api');
        Gate::forUser($user)->authorize('viewAny', Post::class);

        $posts = Post::query()
            ->with('author.profile')
            ->when(! $user?->canManageContent(), fn ($query) => $query->where('status', 'published'))
            ->latest()
            ->paginate(9);

        return PostResource::collection($posts);
    }

    public function store(StorePostRequest $request, PostService $posts): JsonResponse
    {
        $post = $posts->create($request->user(), $request->validated());

        return PostResource::make($post->load('author.profile'))
            ->response()
            ->setStatusCode(Response::HTTP_CREATED);
    }

    public function show(Request $request, Post $post): PostResource
    {
        $user = $request->user('api');
        Gate::forUser($user)->authorize('view', $post);

        return PostResource::make($post->load('author.profile'));
    }

    public function update(
        UpdatePostRequest $request,
        Post $post,
        PostService $posts,
    ): PostResource {
        return PostResource::make(
            $posts->update($post, $request->validated())->load('author.profile'),
        );
    }

    public function destroy(Post $post, PostService $posts): Response
    {
        Gate::authorize('delete', $post);
        $posts->delete($post);

        return response()->noContent();
    }
}
