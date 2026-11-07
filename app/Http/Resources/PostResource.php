<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PostResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->localized('title'),
            'slug' => $this->localized('slug'),
            'preview_img' => $this->preview_img,
            'preview_text' => $this->localized('preview_text'),
            'status' => $this->status,
            'body' => $this->localized('body'),
            'source' => [
                'locale' => config('localization.default'),
                'title' => $this->title,
                'preview_text' => $this->preview_text,
                'body' => $this->body,
            ],
            'author' => $this->whenLoaded('author', fn () => [
                'id' => $this->author->id,
                'name' => $this->author->name,
                'avatar_url' => $this->author->profile?->avatar_path,
            ]),
            'translations' => $this->whenLoaded('translations', fn () => $this->translations->map(fn ($translation) => [
                'locale' => $translation->locale,
                'title' => $translation->title,
                'preview_text' => $translation->preview_text,
                'body' => $translation->body,
            ])->values()),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
