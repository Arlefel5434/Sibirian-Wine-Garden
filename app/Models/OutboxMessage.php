<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class OutboxMessage extends Model
{
    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = ['id', 'type', 'aggregate_type', 'aggregate_id', 'payload', 'attempts', 'available_at', 'claimed_at', 'claim_token', 'published_at', 'failed_at', 'last_error'];

    protected function casts(): array
    {
        return [
            'payload' => 'array',
            'attempts' => 'integer',
            'available_at' => 'datetime',
            'claimed_at' => 'datetime',
            'published_at' => 'datetime',
            'failed_at' => 'datetime',
        ];
    }

    public function processedMessages(): HasMany
    {
        return $this->hasMany(ProcessedMessage::class, 'message_id');
    }
}
