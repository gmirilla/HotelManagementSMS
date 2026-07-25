<?php

declare(strict_types=1);

namespace App\Domain\Payment\DataTransferObjects;

readonly class GatewayRefundResult
{
    public function __construct(
        public bool $successful,
        public string $rawStatus,
    ) {}
}
