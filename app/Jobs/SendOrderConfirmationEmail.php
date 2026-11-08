<?php

namespace App\Jobs;

use App\Models\Order;
use App\Models\OutboxMessage;
use App\Notifications\OrderProcessed;
use App\Services\IdempotentMessageProcessor;
use App\Services\OrderWorkflowService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Throwable;

class SendOrderConfirmationEmail implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 5;

    public int $timeout = 30;

    public function __construct(public readonly string $messageId, public readonly int $orderId)
    {
        $this->onConnection('rabbitmq');
        $this->onQueue('notifications.email');
    }

    public function backoff(): array
    {
        return [10, 60, 300, 900];
    }

    public function handle(IdempotentMessageProcessor $messages, OrderWorkflowService $workflow): void
    {
        $messages->process('send-order-confirmation-email', $this->messageId, function (OutboxMessage $message) use ($workflow) {
            $order = Order::query()->with(['user', 'items'])->whereKey($this->orderId)->lockForUpdate()->firstOrFail();
            $order->user?->notify(new OrderProcessed($order));
            $workflow->complete($order, 'notification');
        });
    }

    public function failed(?Throwable $exception): void
    {
        app(OrderWorkflowService::class)->fail($this->orderId, 'notification', $exception);
    }
}
