<?php

declare(strict_types=1);

namespace App\Domain\Payment\Contracts;

use App\Domain\Payment\DataTransferObjects\GatewayInitiationResult;
use App\Domain\Payment\DataTransferObjects\GatewayRefundResult;
use App\Domain\Payment\DataTransferObjects\GatewayVerificationResult;
use App\Models\Payment;

/**
 * A payment gateway that settles via a hosted checkout page rather than
 * ever receiving card data directly — implementations only ever exchange a
 * reference and an amount with the provider, never a PAN/CVV.
 */
interface PaymentGateway
{
    public function initialize(Payment $payment, string $email, string $callbackUrl): GatewayInitiationResult;

    public function verifyTransaction(string $reference): GatewayVerificationResult;

    public function refund(Payment $payment, ?string $reason): GatewayRefundResult;
}
