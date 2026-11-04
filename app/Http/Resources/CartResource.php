<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CartResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'items' => CartItemResource::collection($this->items),
            'items_count' => $this->items->count(),
            'total_amount' => $this->items->sum(fn ($item) => $item->quantity * $item->sku->price),
            'currency' => $this->items->first()?->sku->currency ?? 'EUR',
        ];
    }
}
