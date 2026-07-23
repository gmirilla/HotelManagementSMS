<?php

declare(strict_types=1);

namespace App\Domain\Payment\Actions;

use App\Domain\FrontDesk\Support\FolioBalanceCalculator;
use App\Domain\Payment\Enums\PaymentStatus;
use App\Models\Folio;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Validation\ValidationException;

/**
 * Records a payment against a folio for the non-gateway methods (cash, POS
 * terminal, bank transfer) that settle synchronously at the front desk.
 * Gateway payments (Stripe/PayPal/Flutterwave/Paystack) go through their own
 * webhook-driven confirmation flow — introduced alongside the Payments
 * module — and are not created via this action.
 */
class RecordFolioPaymentAction
{
    public function __construct(private readonly FolioBalanceCalculator $balanceCalculator) {}

    public function handle(Folio $folio, string $method, int $amountCents, User $staff): Payment
    {
        if ($amountCents <= 0) {
            throw ValidationException::withMessages(['amount' => __('Payment amount must be greater than zero.')]);
        }

        $payment = Payment::create([
            'branch_id' => $folio->branch_id,
            'folio_id' => $folio->id,
            'method' => $method,
            'amount_cents' => $amountCents,
            'currency' => $folio->branch->currency,
            'status' => PaymentStatus::Completed,
            'received_by_user_id' => $staff->id,
        ]);

        $this->balanceCalculator->recalculate($folio);

        return $payment;
    }
}
