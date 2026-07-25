<?php

declare(strict_types=1);

namespace App\Domain\Accounting\Support;

use App\Domain\Accounting\Actions\PostJournalEntryAction;
use App\Domain\Accounting\Enums\JournalSide;
use App\Models\RestaurantOrder;
use App\Models\User;

/**
 * The bridge between a restaurant order with no guest folio and the general
 * ledger. Room-service orders bill to a guest's folio (FR-POS-005) and post
 * to the GL through FolioLedgerPoster when that folio charge is created; a
 * dine-in or takeaway order with no folio to bill has nowhere else to
 * settle, so closing it is treated as an immediate cash sale — the outlet
 * collects payment at the point of closing the check, the same moment the
 * revenue is recognized (no separate payment step exists in the POS today).
 */
class RestaurantLedgerPoster
{
    private const string CASH = '1000';

    private const string RESTAURANT_REVENUE = '4100';

    public function __construct(
        private readonly PostJournalEntryAction $postJournalEntry,
        private readonly BranchAccountResolver $accounts,
    ) {}

    public function postDirectSale(RestaurantOrder $order, ?User $staff): void
    {
        if ($order->total_cents === 0) {
            return;
        }

        $cashAccount = $this->accounts->resolve($order->branch_id, self::CASH);
        $revenueAccount = $this->accounts->resolve($order->branch_id, self::RESTAURANT_REVENUE);

        $this->postJournalEntry->handle(
            branchId: $order->branch_id,
            entryDate: now(),
            lines: [
                ['account_id' => $cashAccount->id, 'side' => JournalSide::Debit, 'amount_cents' => $order->total_cents],
                ['account_id' => $revenueAccount->id, 'side' => JournalSide::Credit, 'amount_cents' => $order->total_cents],
            ],
            memo: "Restaurant order #{$order->id} — {$order->outlet->name} (direct sale)",
            createdBy: $staff,
            reference: $order,
        );
    }
}
