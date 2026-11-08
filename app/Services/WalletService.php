<?php

namespace App\Services;

use App\Models\Order;
use App\Models\Payment;
use App\Models\User;
use App\Models\Wallet;
use App\Models\WalletReservation;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class WalletService
{
    public function get(User $user): Wallet
    {
        return $user->wallet()->firstOrCreate()->load([
            'reservations' => fn ($query) => $query->where('status', 'reserved'),
            'transactions' => fn ($query) => $query->latest()->limit(50),
        ]);
    }

    public function reserve(User $user, Order $order, Payment $payment): WalletReservation
    {
        $wallet = $this->lockedWallet($user);

        if ($wallet->currency !== $order->currency) {
            throw ValidationException::withMessages(['payment' => 'Wallet and order currencies do not match.']);
        }

        $reserved = $wallet->reservations()->where('status', 'reserved')->sum('amount');

        if ($wallet->balance - $reserved < $order->total_amount) {
            throw ValidationException::withMessages(['payment' => 'The wallet balance is insufficient.']);
        }

        return $wallet->reservations()->create([
            'order_id' => $order->id,
            'payment_id' => $payment->id,
            'status' => 'reserved',
            'amount' => $order->total_amount,
        ]);
    }

    public function capture(WalletReservation $reservation): void
    {
        $reservation = WalletReservation::query()->whereKey($reservation->id)->lockForUpdate()->firstOrFail();

        if ($reservation->status === 'captured') {
            return;
        }

        if ($reservation->status !== 'reserved') {
            throw ValidationException::withMessages(['payment' => 'The wallet reservation is no longer available.']);
        }

        $wallet = Wallet::query()->whereKey($reservation->wallet_id)->lockForUpdate()->firstOrFail();

        if ($wallet->balance < $reservation->amount) {
            throw ValidationException::withMessages(['payment' => 'The wallet balance is insufficient.']);
        }

        $before = $wallet->balance;
        $wallet->update(['balance' => $before - $reservation->amount]);
        $wallet->transactions()->create([
            'payment_id' => $reservation->payment_id,
            'order_id' => $reservation->order_id,
            'initiated_by' => $wallet->user_id,
            'type' => 'debit',
            'amount' => $reservation->amount,
            'balance_before' => $before,
            'balance_after' => $wallet->balance,
            'reason' => "Payment for order #{$reservation->order_id}",
        ]);
        $reservation->update(['status' => 'captured']);
    }

    public function release(Order $order): void
    {
        $reservation = WalletReservation::query()->where('order_id', $order->id)->lockForUpdate()->first();

        if ($reservation?->status === 'reserved') {
            $reservation->update(['status' => 'released']);
        }
    }

    public function debit(Payment $payment): void
    {
        $wallet = $this->lockedWallet($payment->order->user);

        if ($wallet->currency !== $payment->currency) {
            throw ValidationException::withMessages(['payment' => 'Wallet and order currencies do not match.']);
        }

        if ($wallet->availableBalance() < $payment->amount) {
            throw ValidationException::withMessages(['payment' => 'The wallet balance is insufficient.']);
        }

        $before = $wallet->balance;
        $wallet->update(['balance' => $before - $payment->amount]);
        $wallet->transactions()->create([
            'payment_id' => $payment->id,
            'order_id' => $payment->order_id,
            'initiated_by' => $payment->order->user_id,
            'type' => 'debit',
            'amount' => $payment->amount,
            'balance_before' => $before,
            'balance_after' => $wallet->balance,
            'reason' => "Payment for order #{$payment->order_id}",
        ]);
    }

    public function adjust(User $user, string $direction, int $amount, User $admin, string $reason): Wallet
    {
        return DB::transaction(function () use ($user, $direction, $amount, $admin, $reason) {
            $wallet = $this->lockedWallet($user);
            $before = $wallet->balance;
            $after = $direction === 'credit' ? $before + $amount : $before - $amount;
            $reserved = $wallet->reservations()->where('status', 'reserved')->sum('amount');

            if ($after < $reserved) {
                throw ValidationException::withMessages(['amount' => 'The adjustment would spend funds reserved for pending orders.']);
            }

            $wallet->update(['balance' => $after]);
            $wallet->transactions()->create([
                'initiated_by' => $admin->id,
                'type' => 'admin_adjustment',
                'amount' => $amount,
                'balance_before' => $before,
                'balance_after' => $after,
                'reason' => $reason,
            ]);

            return $wallet->load(['transactions' => fn ($query) => $query->latest()->limit(50)]);
        });
    }

    private function lockedWallet(User $user): Wallet
    {
        $user->wallet()->firstOrCreate();

        return Wallet::query()->where('user_id', $user->id)->lockForUpdate()->firstOrFail();
    }
}
