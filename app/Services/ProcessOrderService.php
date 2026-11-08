<?php

namespace App\Services;

use App\Models\Order;
use App\Models\OutboxMessage;
use Illuminate\Validation\ValidationException;

class ProcessOrderService
{
    public function __construct(
        private readonly IdempotentMessageProcessor $messages,
        private readonly WalletService $wallets,
        private readonly OutboxService $outbox,
    ) {}

    public function process(string $messageId, int $orderId): void
    {
        $this->messages->process('process-order', $messageId, function (OutboxMessage $message) use ($orderId) {
            $order = Order::query()->with(['payments', 'walletReservation'])->whereKey($orderId)->lockForUpdate()->firstOrFail();

            if ($order->status !== 'pending') {
                throw ValidationException::withMessages(['order' => 'Only pending orders can be processed.']);
            }

            $payment = $order->payments->firstWhere('provider', 'wallet');

            if (! $payment || ! $order->walletReservation) {
                throw ValidationException::withMessages(['payment' => 'The order has no wallet reservation.']);
            }

            $this->wallets->capture($order->walletReservation);
            $payment->update(['status' => 'succeeded', 'provider_payment_id' => 'wallet-'.$payment->id, 'paid_at' => now()]);
            $order->update(['status' => 'paid']);

            foreach (['notification', 'delivery'] as $step) {
                $order->workflowSteps()->create(['name' => $step]);
            }

            $this->outbox->record('OrderConfirmationEmailRequested', Order::class, $order->id, ['order_id' => $order->id]);
            $this->outbox->record('DeliverySubmissionRequested', Order::class, $order->id, ['order_id' => $order->id]);
        });
    }
}
