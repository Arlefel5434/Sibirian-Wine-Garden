<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Order extends Model
{
    protected $fillable = ['user_id', 'status', 'total_amount', 'currency', 'delivery_info'];

    protected function casts(): array
    {
        return ['total_amount' => 'integer', 'delivery_info' => 'array'];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function workflowSteps(): HasMany
    {
        return $this->hasMany(OrderWorkflowStep::class);
    }

    public function walletReservation(): HasOne
    {
        return $this->hasOne(WalletReservation::class);
    }

    public function deliverySubmission(): HasOne
    {
        return $this->hasOne(DeliverySubmission::class);
    }
}
