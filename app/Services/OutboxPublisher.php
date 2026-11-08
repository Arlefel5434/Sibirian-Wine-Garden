<?php

namespace App\Services;

use App\Exceptions\PermanentOutboxException;
use App\Jobs\ProcessOrder;
use App\Jobs\SendOrderConfirmationEmail;
use App\Jobs\SubmitOrderToDelivery;
use App\Models\OutboxMessage;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Throwable;

class OutboxPublisher
{
    public function publish(int $limit = 100): array
    {
        $messages = $this->claim($limit);
        $published = 0;
        $failed = 0;

        foreach ($messages as $message) {
            try {
                Bus::dispatch($this->jobFor($message));
                $message->update([
                    'published_at' => now(),
                    'claimed_at' => null,
                    'claim_token' => null,
                    'last_error' => null,
                ]);
                $published++;
            } catch (PermanentOutboxException $exception) {
                $message->update([
                    'attempts' => $message->attempts + 1,
                    'claimed_at' => null,
                    'claim_token' => null,
                    'failed_at' => now(),
                    'last_error' => Str::limit($exception->getMessage(), 2000, ''),
                ]);
                report($exception);
                $failed++;
            } catch (Throwable $exception) {
                $attempts = $message->attempts + 1;
                $message->update([
                    'attempts' => $attempts,
                    'available_at' => now()->addSeconds(min(300, 2 ** min($attempts, 8))),
                    'claimed_at' => null,
                    'claim_token' => null,
                    'last_error' => Str::limit($exception->getMessage(), 2000, ''),
                ]);
                report($exception);
                $failed++;
            }
        }

        return compact('published', 'failed');
    }

    private function claim(int $limit)
    {
        $claimToken = (string) Str::uuid();

        $ids = DB::transaction(function () use ($limit, $claimToken) {
            $messages = OutboxMessage::query()
                ->whereNull('published_at')
                ->whereNull('failed_at')
                ->where('available_at', '<=', now())
                ->where(function ($query) {
                    $query->whereNull('claimed_at')->orWhere('claimed_at', '<', now()->subMinutes(5));
                })
                ->orderBy('created_at')
                ->lock('FOR UPDATE SKIP LOCKED')
                ->limit(max(1, min($limit, 500)))
                ->get();

            foreach ($messages as $message) {
                $message->update(['claimed_at' => now(), 'claim_token' => $claimToken]);
            }

            return $messages->modelKeys();
        });

        return OutboxMessage::query()->whereIn('id', $ids)->where('claim_token', $claimToken)->orderBy('created_at')->get();
    }

    private function jobFor(OutboxMessage $message): ShouldQueue
    {
        $orderId = (int) ($message->payload['order_id'] ?? 0);

        if ($orderId < 1) {
            throw new PermanentOutboxException("Outbox message {$message->id} has no valid order ID.");
        }

        return match ($message->type) {
            'OrderCreated' => new ProcessOrder($message->id, $orderId),
            'OrderConfirmationEmailRequested' => new SendOrderConfirmationEmail($message->id, $orderId),
            'DeliverySubmissionRequested' => new SubmitOrderToDelivery($message->id, $orderId),
            default => throw new PermanentOutboxException("Unsupported outbox message type {$message->type}."),
        };
    }
}
