<?php

namespace App\DTO;

readonly class PaymentResult
{
    public function __construct(
        public string $status,
        public ?string $confirmationUrl = null,
        public ?string $providerPaymentId = null,
        public array $metadata = [],
    ) {}
}
