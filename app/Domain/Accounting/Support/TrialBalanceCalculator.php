<?php

declare(strict_types=1);

namespace App\Domain\Accounting\Support;

use App\Models\Account;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class TrialBalanceCalculator
{
    public function __construct(private readonly AccountBalanceCalculator $balanceCalculator) {}

    /**
     * @return Collection<int, array{account: Account, debit_cents: int, credit_cents: int}>
     */
    public function forBranch(int $branchId, Carbon $start, Carbon $end): Collection
    {
        return Account::where('branch_id', $branchId)
            ->where('is_active', true)
            ->orderBy('code')
            ->get()
            ->map(function (Account $account) use ($start, $end) {
                $balance = $this->balanceCalculator->balanceForPeriod($account, $start, $end);
                $normalDebit = $account->account_type->increasesOnDebit();

                return [
                    'account' => $account,
                    'debit_cents' => $normalDebit ? max(0, $balance) : max(0, -$balance),
                    'credit_cents' => $normalDebit ? max(0, -$balance) : max(0, $balance),
                ];
            });
    }
}
