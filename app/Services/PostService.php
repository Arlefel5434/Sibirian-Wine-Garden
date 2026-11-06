<?php

namespace App\Services;

use App\Models\Post;
use App\Models\PostTranslation;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class PostService
{
    public function create(User $author, array $data): Post
    {
        return DB::transaction(function () use ($author, $data) {
            $translations = $data['translations'] ?? [];
            unset($data['translations']);
            $post = $author->posts()->create([
                ...$data,
                'slug' => $this->uniqueSlug($data['title']),
            ]);
            $this->syncTranslations($post, $translations);

            return $post;
        });
    }

    public function update(Post $post, array $data): Post
    {
        return DB::transaction(function () use ($post, $data) {
            $translations = $data['translations'] ?? [];
            unset($data['translations']);

            if ($post->title !== $data['title']) {
                $data['slug'] = $this->uniqueSlug($data['title'], $post);
            }

            $post->update($data);
            $this->syncTranslations($post, $translations);

            return $post;
        });
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

    private function syncTranslations(Post $post, array $translations): void
    {
        $translations = collect($translations)
            ->keyBy('locale')
            ->put(config('localization.default'), [
                'locale' => config('localization.default'),
                'title' => $post->title,
                'preview_text' => $post->preview_text,
                'body' => $post->body,
            ]);

        foreach ($translations as $translation) {
            $locale = $translation['locale'];
            $existing = $post->translations()->where('locale', $locale)->first();
            $post->translations()->updateOrCreate(
                ['locale' => $locale],
                [
                    'title' => $translation['title'],
                    'slug' => $this->uniqueTranslationSlug($translation['title'], $locale, $existing),
                    'preview_text' => $translation['preview_text'] ?? null,
                    'body' => $translation['body'],
                ],
            );
        }
    }

    private function uniqueTranslationSlug(string $title, string $locale, ?PostTranslation $ignored = null): string
    {
        $base = Str::slug($title) ?: 'post';
        $slug = $base;
        $suffix = 2;

        while (PostTranslation::query()
            ->where('locale', $locale)
            ->where('slug', $slug)
            ->when($ignored, fn ($query) => $query->whereKeyNot($ignored->getKey()))
            ->exists()) {
            $slug = $base.'-'.$suffix;
            $suffix++;
        }

        return $slug;
    }
}
