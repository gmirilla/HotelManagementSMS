<?php

declare(strict_types=1);

namespace App\Domain\Accounting\Support;

use App\Domain\Accounting\Actions\PostJournalEntryAction;
use App\Domain\Accounting\Enums\JournalSide;
use App\Domain\FrontDesk\Enums\ChargeType;
use App\Models\FolioCharge;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Support\Carbon;

/**
 * The bridge between folio events (charges, payments, refunds) and the
 * general ledger — FolioCharge/Payment-creating Actions call this rather
 * than knowing account codes themselves. Full accrual, not cash-basis: a
 * charge posts Dr Accounts Receivable / Cr Revenue the moment it's charged
 * (FR-ACC-002), independent of whether or when it's ever paid; a payment
 * separately posts Dr Cash / Cr Accounts Receivable, clearing what's owed
 * (FR-ACC-003 AR aging depends on this). A refund is the exact reverse of a
 * payment.
 *
 * Account codes are resolved per branch (FR-ACC-001: chart of accounts is
 * branch-scoped, not global) — see HotelDemoSeeder::seedAccounting() for
 * where these codes come from. A branch missing one of them fails loudly
 * rather than silently skipping the posting or crashing on a null model.
 */
class FolioLedgerPoster
{
    private const string CASH = '1000';

    private const string ACCOUNTS_RECEIVABLE = '1100';

    private const string TAXES_PAYABLE = '2100';

    private const string ROOM_REVENUE = '4000';

    private const string RESTAURANT_REVENUE = '4100';

    public function __construct(
        private readonly PostJournalEntryAction $postJournalEntry,
        private readonly BranchAccountResolver $accounts,
    ) {}

    public function postCharge(FolioCharge $charge, ?User $staff): void
    {
        $branchId = $charge->folio->branch_id;
        $revenueAccountCode = $this->revenueAccountCodeFor($charge->charge_type);
        $amountCents = abs($charge->amount_cents);

        if ($amountCents === 0) {
            return;
        }

        $receivableAccount = $this->accounts->resolve($branchId, self::ACCOUNTS_RECEIVABLE);
        $revenueAccount = $this->accounts->resolve($branchId, $revenueAccountCode);

        // A negative amount_cents is a reversal (see
        // ChangeReservationRoomAction::repriceRemainingNights()) — the exact
        // opposite of a normal charge, undoing what it posted.
        $receivableSide = $charge->amount_cents >= 0 ? JournalSide::Debit : JournalSide::Credit;
        $revenueSide = $charge->amount_cents >= 0 ? JournalSide::Credit : JournalSide::Debit;

        $this->postJournalEntry->handle(
            branchId: $branchId,
            entryDate: Carbon::parse($charge->charge_date),
            lines: [
                ['account_id' => $receivableAccount->id, 'side' => $receivableSide, 'amount_cents' => $amountCents],
                ['account_id' => $revenueAccount->id, 'side' => $revenueSide, 'amount_cents' => $amountCents],
            ],
            memo: "Folio charge — {$charge->description}",
            createdBy: $staff,
            reference: $charge,
        );
    }

    public function postPayment(Payment $payment, ?User $staff): void
    {
        $cashAccount = $this->accounts->resolve($payment->branch_id, self::CASH);
        $receivableAccount = $this->accounts->resolve($payment->branch_id, self::ACCOUNTS_RECEIVABLE);

        $this->postJournalEntry->handle(
            branchId: $payment->branch_id,
            entryDate: now(),
            lines: [
                ['account_id' => $cashAccount->id, 'side' => JournalSide::Debit, 'amount_cents' => $payment->amount_cents],
                ['account_id' => $receivableAccount->id, 'side' => JournalSide::Credit, 'amount_cents' => $payment->amount_cents],
            ],
            memo: 'Payment received — ' . $payment->method->value,
            createdBy: $staff,
            reference: $payment,
        );
    }

    public function postRefund(Payment $payment, ?User $staff): void
    {
        $cashAccount = $this->accounts->resolve($payment->branch_id, self::CASH);
        $receivableAccount = $this->accounts->resolve($payment->branch_id, self::ACCOUNTS_RECEIVABLE);

        $this->postJournalEntry->handle(
            branchId: $payment->branch_id,
            entryDate: now(),
            lines: [
                ['account_id' => $receivableAccount->id, 'side' => JournalSide::Debit, 'amount_cents' => $payment->amount_cents],
                ['account_id' => $cashAccount->id, 'side' => JournalSide::Credit, 'amount_cents' => $payment->amount_cents],
            ],
            memo: 'Payment refunded — ' . $payment->method->value,
            createdBy: $staff,
            reference: $payment,
        );
    }

    private function revenueAccountCodeFor(ChargeType $chargeType): string
    {
        return match ($chargeType) {
            ChargeType::Tax => self::TAXES_PAYABLE,
            ChargeType::Restaurant => self::RESTAURANT_REVENUE,
            ChargeType::Room, ChargeType::ServiceFee, ChargeType::LateCheckout,
            ChargeType::EarlyCheckin, ChargeType::Misc, ChargeType::Discount => self::ROOM_REVENUE,
        };
    }
}
