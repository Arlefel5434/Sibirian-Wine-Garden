<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AdminOrderResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'status' => $this->status,
            'total_amount' => $this->total_amount,
            'currency' => $this->currency,
            'customer' => UserResource::make($this->whenLoaded('user')),
            'items_count' => $this->items_count,
            'payment_status' => $this->payments->first()?->status,
            'created_at' => $this->created_at?->toISOString(),
        ];
    }
}
