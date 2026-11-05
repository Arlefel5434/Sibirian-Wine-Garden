<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Payment extends Model
{
    protected $fillable = ['order_id', 'provider', 'status', 'amount', 'currency', 'idempotency_key', 'provider_payment_id', 'confirmation_url', 'metadata', 'paid_at'];

    protected function casts(): array
    {
        return ['amount' => 'integer', 'metadata' => 'array', 'paid_at' => 'datetime'];
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }
}
