<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CartItemResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'quantity' => $this->quantity,
            'line_total' => $this->quantity * $this->sku->price,
            'sku' => SkuResource::make($this->sku),
            'product' => ['id' => $this->sku->product->id, 'name' => $this->sku->product->name, 'slug' => $this->sku->product->slug],
        ];
    }
}
