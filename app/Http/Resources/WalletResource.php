<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class WalletResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'user_id' => $this->user_id,
            'balance' => $this->balance,
            'currency' => $this->currency,
            'transactions' => $this->whenLoaded('transactions', fn () => $this->transactions->map(fn ($transaction) => [
                'id' => $transaction->id,
                'type' => $transaction->type,
                'amount' => $transaction->amount,
                'balance_before' => $transaction->balance_before,
                'balance_after' => $transaction->balance_after,
                'order_id' => $transaction->order_id,
                'reason' => $transaction->reason,
                'created_at' => $transaction->created_at?->toISOString(),
            ])),
        ];
    }
}
