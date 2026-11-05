<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderItem extends Model
{
    protected $fillable = ['sku_id', 'quantity', 'price_at_purchase', 'product_name', 'sku_code', 'variant_name', 'variant_attributes', 'currency', 'quantity_unit'];

    protected function casts(): array
    {
        return ['quantity' => 'integer', 'price_at_purchase' => 'integer', 'variant_attributes' => 'array'];
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function sku(): BelongsTo
    {
        return $this->belongsTo(Sku::class);
    }
}
