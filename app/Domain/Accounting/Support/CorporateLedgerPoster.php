<?php

declare(strict_types=1);

namespace App\Domain\Accounting\Support;

use App\Domain\Accounting\Actions\PostJournalEntryAction;
use App\Domain\Accounting\Enums\JournalSide;
use App\Models\ApEntry;
use App\Models\ArEntry;
use App\Models\User;

/**
 * The bridge between corporate receivable/payable payments and the general
 * ledger — the counterpart to FolioLedgerPoster for the corporate-account
 * and supplier side of Accounting rather than guest folios. An AR payment
 * is money coming in against a corporate account's balance (Dr Cash / Cr
 * Accounts Receivable); an AP payment is money going out to a supplier
 * (Dr Accounts Payable / Cr Cash). Unlike folios, corporate AR/AP entries
 * don't post a charge-side entry themselves — they're created directly at
 * the invoiced/billed amount — so only the payment side needs wiring here.
 */
class CorporateLedgerPoster
{
    private const string CASH = '1000';

    private const string ACCOUNTS_RECEIVABLE = '1100';

    private const string ACCOUNTS_PAYABLE = '2000';

    public function __construct(
        private readonly PostJournalEntryAction $postJournalEntry,
        private readonly BranchAccountResolver $accounts,
    ) {}

    public function postArPayment(ArEntry $entry, int $amountCents, ?User $staff): void
    {
        $cashAccount = $this->accounts->resolve($entry->branch_id, self::CASH);
        $receivableAccount = $this->accounts->resolve($entry->branch_id, self::ACCOUNTS_RECEIVABLE);

        $this->postJournalEntry->handle(
            branchId: $entry->branch_id,
            entryDate: now(),
            lines: [
                ['account_id' => $cashAccount->id, 'side' => JournalSide::Debit, 'amount_cents' => $amountCents],
                ['account_id' => $receivableAccount->id, 'side' => JournalSide::Credit, 'amount_cents' => $amountCents],
            ],
            memo: 'Corporate receivable payment received',
            createdBy: $staff,
            reference: $entry,
        );
    }

    public function postApPayment(ApEntry $entry, int $amountCents, ?User $staff): void
    {
        $payableAccount = $this->accounts->resolve($entry->branch_id, self::ACCOUNTS_PAYABLE);
        $cashAccount = $this->accounts->resolve($entry->branch_id, self::CASH);

        $this->postJournalEntry->handle(
            branchId: $entry->branch_id,
            entryDate: now(),
            lines: [
                ['account_id' => $payableAccount->id, 'side' => JournalSide::Debit, 'amount_cents' => $amountCents],
                ['account_id' => $cashAccount->id, 'side' => JournalSide::Credit, 'amount_cents' => $amountCents],
            ],
            memo: 'Supplier payable payment made',
            createdBy: $staff,
            reference: $entry,
        );
    }
}
