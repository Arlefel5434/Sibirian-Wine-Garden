<?php

namespace App\Services\Payments;

use App\Contracts\PaymentGateway;
use InvalidArgumentException;

class PaymentGatewayManager
{
    public function __construct(
        private readonly FakePaymentGateway $fake,
        private readonly WalletPaymentGateway $wallet,
    ) {}

    public function for(string $provider): PaymentGateway
    {
        return match ($provider) {
            'fake' => $this->fake,
            'wallet' => $this->wallet,
            default => throw new InvalidArgumentException('Unsupported payment provider.'),
        };
    }
}
