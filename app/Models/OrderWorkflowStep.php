<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderWorkflowStep extends Model
{
    protected $fillable = ['order_id', 'name', 'status', 'completed_at', 'failed_at', 'last_error'];

    protected function casts(): array
    {
        return ['completed_at' => 'datetime', 'failed_at' => 'datetime'];
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }
}
