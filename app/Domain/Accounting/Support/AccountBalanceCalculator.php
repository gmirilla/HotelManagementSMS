<?php

declare(strict_types=1);

namespace App\Domain\Accounting\Support;

use App\Models\Account;
use App\Models\JournalLine;
use Illuminate\Support\Carbon;

/**
 * Computes an account's balance signed per its normal-balance convention
 * (positive for assets/expenses means "more debit than credit", positive
 * for liabilities/equity/revenue means "more credit than debit") — never
 * stored, always derived from journal_lines, for the same reason folio and
 * inventory balances are: the ledger is the only source of truth.
 */
class AccountBalanceCalculator
{
    public function balanceAsOf(Account $account, Carbon $asOfDate): int
    {
        return $this->balance($account, null, $asOfDate);
    }

    public function balanceForPeriod(Account $account, Carbon $start, Carbon $end): int
    {
        return $this->balance($account, $start, $end);
    }

    private function balance(Account $account, ?Carbon $start, Carbon $end): int
    {
        $query = JournalLine::query()
            ->where('account_id', $account->id)
            ->whereHas('journalEntry', function ($q) use ($start, $end) {
                $q->where('entry_date', '<=', $end);

                if ($start instanceof Carbon) {
                    $q->where('entry_date', '>=', $start);
                }
            });

        $debits = (int) (clone $query)->where('side', 'debit')->sum('amount_cents');
        $credits = (int) (clone $query)->where('side', 'credit')->sum('amount_cents');

        return $account->account_type->increasesOnDebit() ? $debits - $credits : $credits - $debits;
    }
}
