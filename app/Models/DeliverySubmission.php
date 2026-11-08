<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DeliverySubmission extends Model
{
    protected $fillable = ['order_id', 'provider', 'status', 'provider_reference', 'payload', 'submitted_at', 'last_error'];

    protected function casts(): array
    {
        return ['payload' => 'array', 'submitted_at' => 'datetime'];
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }
}
