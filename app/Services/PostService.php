<?php

namespace App\Services;

use App\Models\Post;
use App\Models\User;
use Illuminate\Support\Str;

class PostService
{
    public function create(User $author, array $data): Post
    {
        return $author->posts()->create([
            ...$data,
            'slug' => $this->uniqueSlug($data['title']),
        ]);
    }

    public function update(Post $post, array $data): Post
    {
        if ($post->title !== $data['title']) {
            $data['slug'] = $this->uniqueSlug($data['title'], $post);
        }

        $post->update($data);

        return $post;
    }

    public function delete(Post $post): void
    {
        $post->delete();
    }

    private function uniqueSlug(string $title, ?Post $ignoredPost = null): string
    {
        $base = Str::slug($title) ?: 'post';
        $slug = $base;
        $suffix = 2;

        while (Post::query()
            ->where('slug', $slug)
            ->when($ignoredPost, fn ($query) => $query->whereKeyNot($ignoredPost->getKey()))
            ->exists()) {
            $slug = $base.'-'.$suffix;
            $suffix++;
        }

        return $slug;
    }
}
