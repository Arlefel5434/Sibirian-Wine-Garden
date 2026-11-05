<?php

namespace App\Contracts;

use App\DTO\PaymentResult;
use App\Models\Payment;

interface PaymentGateway
{
    public function initiate(Payment $payment): PaymentResult;
}
