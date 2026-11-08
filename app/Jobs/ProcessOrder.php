<?php

namespace App\Jobs;

use App\Services\OrderFailureService;
use App\Services\ProcessOrderService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Throwable;

class ProcessOrder implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $timeout = 30;

    public function __construct(public readonly string $messageId, public readonly int $orderId)
    {
        $this->onConnection('rabbitmq');
        $this->onQueue('orders.process');
    }

    public function backoff(): array
    {
        return [5, 30, 120];
    }

    public function handle(ProcessOrderService $orders): void
    {
        $orders->process($this->messageId, $this->orderId);
    }

    public function failed(?Throwable $exception): void
    {
        app(OrderFailureService::class)->cancelPending($this->orderId);
    }
}
