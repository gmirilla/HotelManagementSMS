<?php

declare(strict_types=1);

namespace App\Domain\Payment\Actions;

use App\Domain\Accounting\Support\FolioLedgerPoster;
use App\Domain\FrontDesk\Support\FolioBalanceCalculator;
use App\Domain\Payment\Enums\PaymentStatus;
use App\Domain\Payment\Gateways\PaystackGateway;
use App\Models\Payment;

/**
 * The single place a gateway payment ever becomes Completed or Failed.
 * Called from two independent triggers — the browser's callback redirect
 * and Paystack's async webhook — in either order, so it must be idempotent:
 * once a payment has left Pending, this is a no-op. Never trusts the
 * caller's idea of what happened; always re-verifies server-to-server and
 * cross-checks the amount before accepting a success.
 */
class ConfirmGatewayPaymentAction
{
    public function __construct(
        private readonly FolioBalanceCalculator $balanceCalculator,
        private readonly FolioLedgerPoster $ledgerPoster,
    ) {}

    public function handle(string $reference): Payment
    {
        $payment = Payment::where('gateway_reference', $reference)->firstOrFail();

        if ($payment->status !== PaymentStatus::Pending) {
            return $payment;
        }

        $gateway = PaystackGateway::forTenant($payment->branch->tenant);
        $result = $gateway->verifyTransaction($reference);

        if (! $result->successful || $result->amountCents !== $payment->amount_cents) {
            $payment->update(['status' => PaymentStatus::Failed]);

            return $payment->fresh();
        }

        $payment->update(['status' => PaymentStatus::Completed, 'paid_at' => now()]);

        if ($payment->folio) {
            $this->balanceCalculator->recalculate($payment->folio);
        }

        // No $staff here — this can be triggered by an unauthenticated
        // webhook request, not just the browser callback.
        $this->ledgerPoster->postPayment($payment, null);

        return $payment->fresh();
    }
}
