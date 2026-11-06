<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AdminUserResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'phone_number' => $this->profile?->phone_number,
            'role' => $this->profile?->role,
            'is_banned' => $this->ban !== null,
            'ban_reason' => $this->ban?->reason,
            'banned_at' => $this->ban?->created_at?->toISOString(),
            'orders_count' => $this->orders_count,
            'created_at' => $this->created_at?->toISOString(),
        ];
    }
}
