<?php

namespace App\Services;

use App\Models\Payment;
use App\Models\User;
use App\Models\Wallet;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class WalletService
{
    public function get(User $user): Wallet
    {
        return $user->wallet()->firstOrCreate()->load(['transactions' => fn ($query) => $query->latest()->limit(50)]);
    }

    public function debit(Payment $payment): void
    {
        $wallet = $this->lockedWallet($payment->order->user);

        if ($wallet->currency !== $payment->currency) {
            throw ValidationException::withMessages(['payment' => 'Wallet and order currencies do not match.']);
        }

        if ($wallet->balance < $payment->amount) {
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

            if ($after < 0) {
                throw ValidationException::withMessages(['amount' => 'The adjustment would make the balance negative.']);
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
