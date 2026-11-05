<?php

namespace App\Services;

use App\Models\Order;
use App\Models\Payment;
use App\Models\User;
use App\Services\Payments\PaymentGatewayManager;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PaymentService
{
    public function __construct(private readonly PaymentGatewayManager $gateways) {}

    public function create(User $user, Order $order, string $provider, string $idempotencyKey): Payment
    {
        return DB::transaction(function () use ($user, $order, $provider, $idempotencyKey) {
            $order = Order::query()->whereKey($order->id)->where('user_id', $user->id)->lockForUpdate()->firstOrFail();
            $existing = Payment::query()->where('idempotency_key', $idempotencyKey)->first();

            if ($existing) {
                if ($existing->order_id !== $order->id || $existing->provider !== $provider) {
                    throw ValidationException::withMessages(['idempotency_key' => 'This idempotency key was already used for another payment.']);
                }

                return $existing->load('order.items');
            }

            if ($order->status !== 'pending') {
                throw ValidationException::withMessages(['order' => 'Only pending orders can be paid.']);
            }

            if ($order->payments()->where('status', 'pending')->exists()) {
                throw ValidationException::withMessages(['payment' => 'This order already has a pending payment.']);
            }

            if ($provider === 'fake' && ! app()->environment(['local', 'testing'])) {
                abort(404);
            }

            $payment = $order->payments()->create([
                'provider' => $provider,
                'amount' => $order->total_amount,
                'currency' => $order->currency,
                'idempotency_key' => $idempotencyKey,
            ]);
            $result = $this->gateways->for($provider)->initiate($payment->load('order.user'));
            $payment->update([
                'status' => $result->status,
                'confirmation_url' => $result->confirmationUrl,
                'provider_payment_id' => $result->providerPaymentId,
                'metadata' => $result->metadata,
                'paid_at' => $result->status === 'succeeded' ? now() : null,
            ]);

            if ($result->status === 'succeeded') {
                $order->update(['status' => 'paid']);
            }

            return $payment->load('order.items');
        });
    }

    public function simulate(User $user, Payment $payment, string $outcome): Payment
    {
        abort_unless(app()->environment(['local', 'testing']), 404);

        return DB::transaction(function () use ($user, $payment, $outcome) {
            $payment = Payment::query()->with('order.items')->whereKey($payment->id)->lockForUpdate()->firstOrFail();

            abort_unless($payment->order->user_id === $user->id && $payment->provider === 'fake', 404);

            if ($payment->status !== 'pending') {
                return $payment;
            }

            $status = match ($outcome) {
                'succeeded' => 'succeeded',
                'failed' => 'failed',
                'canceled' => 'canceled',
            };
            $payment->update(['status' => $status, 'paid_at' => $status === 'succeeded' ? now() : null]);

            if ($status === 'succeeded') {
                $payment->order->update(['status' => 'paid']);
            }

            return $payment->refresh()->load('order.items');
        });
    }
}
