<?php

namespace App\Services;

use App\Models\OutboxMessage;
use Illuminate\Support\Str;

class OutboxService
{
    public function record(string $type, string $aggregateType, int $aggregateId, array $payload): OutboxMessage
    {
        return OutboxMessage::query()->create([
            'id' => (string) Str::uuid(),
            'type' => $type,
            'aggregate_type' => $aggregateType,
            'aggregate_id' => $aggregateId,
            'payload' => $payload,
            'available_at' => now(),
        ]);
    }
}
