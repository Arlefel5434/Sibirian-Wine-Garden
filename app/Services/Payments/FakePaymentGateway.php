<?php

namespace App\Services\Payments;

use App\Contracts\PaymentGateway;
use App\DTO\PaymentResult;
use App\Models\Payment;

class FakePaymentGateway implements PaymentGateway
{
    public function initiate(Payment $payment): PaymentResult
    {
        return new PaymentResult(
            status: 'pending',
            confirmationUrl: route('payments.simulate', $payment),
            providerPaymentId: 'fake-'.$payment->id,
        );
    }
}
