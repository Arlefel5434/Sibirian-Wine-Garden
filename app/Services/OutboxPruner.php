<?php

namespace App\Services;

use App\Models\OutboxMessage;
use App\Models\ProcessedMessage;
use Illuminate\Support\Facades\DB;

class OutboxPruner
{
    private const CONSUMERS = [
        'OrderCreated' => 'process-order',
        'OrderConfirmationEmailRequested' => 'send-order-confirmation-email',
        'DeliverySubmissionRequested' => 'submit-order-to-delivery',
    ];

    public function prune(int $days): array
    {
        $threshold = now()->subDays(max(1, $days));
        $messages = 0;
        $processed = 0;

        foreach (self::CONSUMERS as $type => $consumer) {
            do {
                $ids = OutboxMessage::query()
                    ->where('type', $type)
                    ->whereNotNull('published_at')
                    ->where('published_at', '<=', $threshold)
                    ->whereHas('processedMessages', fn ($query) => $query->where('consumer', $consumer))
                    ->limit(500)
                    ->pluck('id');

                if ($ids->isEmpty()) {
                    break;
                }

                [$deletedMessages, $deletedProcessed] = DB::transaction(function () use ($ids) {
                    $deletedProcessed = ProcessedMessage::query()->whereIn('message_id', $ids)->delete();
                    $deletedMessages = OutboxMessage::query()->whereKey($ids->all())->delete();

                    return [$deletedMessages, $deletedProcessed];
                });

                $messages += $deletedMessages;
                $processed += $deletedProcessed;
            } while ($ids->count() === 500);
        }

        return compact('messages', 'processed');
    }
}
