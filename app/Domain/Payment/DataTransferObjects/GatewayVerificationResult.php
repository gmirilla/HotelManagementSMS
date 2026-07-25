<?php

declare(strict_types=1);

namespace App\Domain\Payment\DataTransferObjects;

readonly class GatewayVerificationResult
{
    public function __construct(
        public bool $successful,
        public string $reference,
        public int $amountCents,
        public string $currency,
        public string $rawStatus,
    ) {}
}
