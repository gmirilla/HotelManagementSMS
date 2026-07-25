<?php

declare(strict_types=1);

namespace App\Domain\Payment\Actions;

use App\Domain\FrontDesk\Support\FolioBalanceCalculator;
use App\Domain\Payment\Enums\PaymentStatus;
use App\Domain\Payment\Gateways\PaystackGateway;
use App\Models\Payment;
use Illuminate\Validation\ValidationException;

/**
 * Refunds go through the gateway's own API, never a local status edit —
 * anything else would leave our ledger disagreeing with what actually
 * happened to the guest's money. Full refunds only for v1: PaymentStatus
 * has a PartiallyRefunded case for later, not used here.
 */
class RefundGatewayPaymentAction
{
    public function __construct(private readonly FolioBalanceCalculator $balanceCalculator) {}

    public function handle(Payment $payment, ?string $reason): Payment
    {
        if ($payment->status !== PaymentStatus::Completed) {
            throw ValidationException::withMessages(['status' => __('Only a completed payment can be refunded.')]);
        }

        if (! $payment->method->isGateway()) {
            throw ValidationException::withMessages(['method' => __('Only gateway payments can be refunded through this action.')]);
        }

        $gateway = PaystackGateway::forTenant($payment->branch->tenant);
        $result = $gateway->refund($payment, $reason);

        if (! $result->successful) {
            throw ValidationException::withMessages(['refund' => __('The refund could not be processed (gateway status: :status).', ['status' => $result->rawStatus])]);
        }

        $payment->update(['status' => PaymentStatus::Refunded, 'refund_reason' => $reason]);

        if ($payment->folio) {
            $this->balanceCalculator->recalculate($payment->folio);
        }

        return $payment->fresh();
    }
}
