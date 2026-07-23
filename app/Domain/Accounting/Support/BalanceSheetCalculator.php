<?php

declare(strict_types=1);

namespace App\Domain\Accounting\Support;

use App\Domain\Accounting\Enums\AccountType;
use App\Models\Account;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class BalanceSheetCalculator
{
    public function __construct(private readonly AccountBalanceCalculator $balanceCalculator) {}

    /**
     * @return array{assets: Collection<int, array{account: Account, amount_cents: int}>, liabilities: Collection<int, array{account: Account, amount_cents: int}>, equity: Collection<int, array{account: Account, amount_cents: int}>, total_assets_cents: int, total_liabilities_cents: int, total_equity_cents: int}
     */
    public function forBranch(int $branchId, Carbon $asOfDate): array
    {
        $assets = $this->accountBalances($branchId, AccountType::Asset, $asOfDate);
        $liabilities = $this->accountBalances($branchId, AccountType::Liability, $asOfDate);
        $equity = $this->accountBalances($branchId, AccountType::Equity, $asOfDate);

        return [
            'assets' => $assets,
            'liabilities' => $liabilities,
            'equity' => $equity,
            'total_assets_cents' => $assets->sum('amount_cents'),
            'total_liabilities_cents' => $liabilities->sum('amount_cents'),
            'total_equity_cents' => $equity->sum('amount_cents'),
        ];
    }

    /**
     * @return Collection<int, array{account: Account, amount_cents: int}>
     */
    private function accountBalances(int $branchId, AccountType $type, Carbon $asOfDate): Collection
    {
        return Account::where('branch_id', $branchId)
            ->where('account_type', $type)
            ->where('is_active', true)
            ->orderBy('code')
            ->get()
            ->map(fn (Account $account) => [
                'account' => $account,
                'amount_cents' => $this->balanceCalculator->balanceAsOf($account, $asOfDate),
            ])
            ->filter(fn (array $row) => $row['amount_cents'] !== 0)
            ->values();
    }
}
