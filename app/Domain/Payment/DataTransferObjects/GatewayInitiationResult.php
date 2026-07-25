<?php

declare(strict_types=1);

namespace App\Domain\Payment\DataTransferObjects;

readonly class GatewayInitiationResult
{
    public function __construct(
        public string $authorizationUrl,
        public string $reference,
    ) {}
}
