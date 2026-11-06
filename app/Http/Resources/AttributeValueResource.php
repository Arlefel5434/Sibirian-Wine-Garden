<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AttributeValueResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'attribute_id' => $this->attribute_id,
            'name' => $this->whenLoaded('attribute', fn () => $this->attribute->localized('name')),
            'type' => $this->whenLoaded('attribute', fn () => $this->attribute->type),
            'unit' => $this->whenLoaded('attribute', fn () => $this->attribute->localized('unit')),
            'value' => $this->localized('value'),
            'canonical_value' => $this->value,
        ];
    }
}
