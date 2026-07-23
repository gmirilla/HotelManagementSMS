<?php

declare(strict_types=1);

namespace App\Domain\Accounting\Support;

use App\Domain\Accounting\Enums\AccountType;
use App\Models\Account;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class ProfitAndLossCalculator
{
    public function __construct(private readonly AccountBalanceCalculator $balanceCalculator) {}

    /**
     * @return array{revenue: Collection<int, array{account: Account, amount_cents: int}>, expenses: Collection<int, array{account: Account, amount_cents: int}>, total_revenue_cents: int, total_expense_cents: int, net_income_cents: int}
     */
    public function forBranch(int $branchId, Carbon $start, Carbon $end): array
    {
        $revenueAccounts = $this->accountBalances($branchId, AccountType::Revenue, $start, $end);
        $expenseAccounts = $this->accountBalances($branchId, AccountType::Expense, $start, $end);

        $totalRevenue = $revenueAccounts->sum('amount_cents');
        $totalExpense = $expenseAccounts->sum('amount_cents');

        return [
            'revenue' => $revenueAccounts,
            'expenses' => $expenseAccounts,
            'total_revenue_cents' => $totalRevenue,
            'total_expense_cents' => $totalExpense,
            'net_income_cents' => $totalRevenue - $totalExpense,
        ];
    }

    /**
     * @return Collection<int, array{account: Account, amount_cents: int}>
     */
    private function accountBalances(int $branchId, AccountType $type, Carbon $start, Carbon $end): Collection
    {
        return Account::where('branch_id', $branchId)
            ->where('account_type', $type)
            ->where('is_active', true)
            ->orderBy('code')
            ->get()
            ->map(fn (Account $account) => [
                'account' => $account,
                'amount_cents' => $this->balanceCalculator->balanceForPeriod($account, $start, $end),
            ])
            ->filter(fn (array $row) => $row['amount_cents'] !== 0)
            ->values();
    }
}
