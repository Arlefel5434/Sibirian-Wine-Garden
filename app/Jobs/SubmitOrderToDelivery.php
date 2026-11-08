<?php

namespace App\Jobs;

use App\Models\Order;
use App\Models\OutboxMessage;
use App\Services\DeliveryService;
use App\Services\IdempotentMessageProcessor;
use App\Services\OrderWorkflowService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Throwable;

class SubmitOrderToDelivery implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 5;

    public int $timeout = 30;

    public function __construct(public readonly string $messageId, public readonly int $orderId)
    {
        $this->onConnection('rabbitmq');
        $this->onQueue('delivery.submit');
    }

    public function backoff(): array
    {
        return [10, 60, 300, 900];
    }

    public function handle(IdempotentMessageProcessor $messages, DeliveryService $delivery, OrderWorkflowService $workflow): void
    {
        $messages->process('submit-order-to-delivery', $this->messageId, function (OutboxMessage $message) use ($delivery, $workflow) {
            $order = Order::query()->with('items')->whereKey($this->orderId)->lockForUpdate()->firstOrFail();
            $delivery->submit($order);
            $workflow->complete($order, 'delivery');
        });
    }

    public function failed(?Throwable $exception): void
    {
        app(OrderWorkflowService::class)->fail($this->orderId, 'delivery', $exception);
    }
}
