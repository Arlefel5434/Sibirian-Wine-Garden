<?php

namespace App\Services;

use App\Models\OutboxMessage;
use App\Models\ProcessedMessage;
use Closure;
use Illuminate\Support\Facades\DB;

class IdempotentMessageProcessor
{
    public function process(string $consumer, string $messageId, Closure $handler): bool
    {
        return DB::transaction(function () use ($consumer, $messageId, $handler) {
            if (ProcessedMessage::query()->where('consumer', $consumer)->where('message_id', $messageId)->exists()) {
                return false;
            }

            $message = OutboxMessage::query()->whereKey($messageId)->lockForUpdate()->firstOrFail();

            if (ProcessedMessage::query()->where('consumer', $consumer)->where('message_id', $messageId)->exists()) {
                return false;
            }

            $handler($message);

            ProcessedMessage::query()->create([
                'consumer' => $consumer,
                'message_id' => $messageId,
                'processed_at' => now(),
            ]);

            return true;
        });
    }
}
