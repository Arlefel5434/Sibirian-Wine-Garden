<?php

namespace App\Services\Payments;

use App\Contracts\PaymentGateway;
use App\DTO\PaymentResult;
use App\Models\Payment;
use App\Services\WalletService;

class WalletPaymentGateway implements PaymentGateway
{
    public function __construct(private readonly WalletService $wallets) {}

    public function initiate(Payment $payment): PaymentResult
    {
        $this->wallets->debit($payment);

        return new PaymentResult(status: 'succeeded', providerPaymentId: 'wallet-'.$payment->id);
    }
}
