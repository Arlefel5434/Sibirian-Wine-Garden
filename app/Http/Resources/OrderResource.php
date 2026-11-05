<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OrderResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'status' => $this->status,
            'total_amount' => $this->total_amount,
            'currency' => $this->currency,
            'delivery_info' => $this->delivery_info,
            'items' => $this->items->map(fn ($item) => ['id' => $item->id, 'product_name' => $item->product_name, 'variant_name' => $item->variant_name, 'quantity' => $item->quantity, 'price' => $item->price_at_purchase, 'currency' => $item->currency]),
            'payments' => PaymentResource::collection($this->whenLoaded('payments')),
            'created_at' => $this->created_at?->toISOString(),
        ];
    }
}
