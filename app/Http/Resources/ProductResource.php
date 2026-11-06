<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->localized('name'),
            'slug' => $this->localized('slug'),
            'description' => $this->localized('description'),
            'source' => [
                'locale' => config('localization.default'),
                'name' => $this->name,
                'description' => $this->description,
            ],
            'status' => $this->status,
            'is_favorite' => (bool) ($this->is_favorite ?? false),
            'category' => $this->whenLoaded('category', fn () => $this->category ? [
                'id' => $this->category->id,
                'name' => $this->category->localized('name'),
                'slug' => $this->category->localized('slug'),
            ] : null),
            'skus' => SkuResource::collection($this->whenLoaded('skus')),
            'attributes' => AttributeValueResource::collection($this->whenLoaded('attributeValues')),
            'translations' => $this->whenLoaded('translations', fn () => $this->translations->map(fn ($translation) => [
                'locale' => $translation->locale,
                'name' => $translation->name,
                'description' => $translation->description,
            ])->values()),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
