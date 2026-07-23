<?php

declare(strict_types=1);

use App\Domain\Accounting\Actions\PostJournalEntryAction;
use App\Domain\Accounting\Enums\AccountType;
use App\Domain\Accounting\Support\AccountBalanceCalculator;
use App\Domain\Accounting\Support\BalanceSheetCalculator;
use App\Domain\Accounting\Support\ProfitAndLossCalculator;
use App\Domain\Accounting\Support\TrialBalanceCalculator;
use App\Models\Account;
use App\Models\Branch;
use Illuminate\Support\Carbon;

beforeEach(function (): void {
    $this->branch = Branch::factory()->create();
    $this->cash = Account::factory()->create(['branch_id' => $this->branch->id, 'code' => '1000', 'account_type' => AccountType::Asset]);
    $this->revenue = Account::factory()->create(['branch_id' => $this->branch->id, 'code' => '4000', 'account_type' => AccountType::Revenue]);
    $this->expense = Account::factory()->create(['branch_id' => $this->branch->id, 'code' => '5000', 'account_type' => AccountType::Expense]);
    $this->equity = Account::factory()->create(['branch_id' => $this->branch->id, 'code' => '3000', 'account_type' => AccountType::Equity]);

    // Owner injects 100.00 capital, then the business earns 50.00 revenue and pays 20.00 expense in cash.
    app(PostJournalEntryAction::class)->handle(
        branchId: $this->branch->id,
        entryDate: Carbon::parse('2026-07-01'),
        lines: [
            ['account_id' => $this->cash->id, 'side' => 'debit', 'amount_cents' => 10000],
            ['account_id' => $this->equity->id, 'side' => 'credit', 'amount_cents' => 10000],
        ],
    );

    app(PostJournalEntryAction::class)->handle(
        branchId: $this->branch->id,
        entryDate: Carbon::parse('2026-07-05'),
        lines: [
            ['account_id' => $this->cash->id, 'side' => 'debit', 'amount_cents' => 5000],
            ['account_id' => $this->revenue->id, 'side' => 'credit', 'amount_cents' => 5000],
        ],
    );

    app(PostJournalEntryAction::class)->handle(
        branchId: $this->branch->id,
        entryDate: Carbon::parse('2026-07-10'),
        lines: [
            ['account_id' => $this->expense->id, 'side' => 'debit', 'amount_cents' => 2000],
            ['account_id' => $this->cash->id, 'side' => 'credit', 'amount_cents' => 2000],
        ],
    );
});

test('account balance calculator signs balances per the account normal-balance convention', function (): void {
    $calculator = app(AccountBalanceCalculator::class);
    $asOf = Carbon::parse('2026-07-31');

    expect($calculator->balanceAsOf($this->cash, $asOf))->toBe(13000)
        ->and($calculator->balanceAsOf($this->revenue, $asOf))->toBe(5000)
        ->and($calculator->balanceAsOf($this->expense, $asOf))->toBe(2000)
        ->and($calculator->balanceAsOf($this->equity, $asOf))->toBe(10000);
});

test('account balance calculator scopes to a period window', function (): void {
    $calculator = app(AccountBalanceCalculator::class);

    // Only the 2026-07-05 revenue posting falls inside this window.
    $balance = $calculator->balanceForPeriod($this->cash, Carbon::parse('2026-07-02'), Carbon::parse('2026-07-08'));

    expect($balance)->toBe(5000);
});

test('trial balance is always balanced — total debits equal total credits', function (): void {
    $rows = app(TrialBalanceCalculator::class)->forBranch($this->branch->id, Carbon::parse('2026-07-01'), Carbon::parse('2026-07-31'));

    expect($rows->sum('debit_cents'))->toBe($rows->sum('credit_cents'));
});

test('profit and loss nets revenue against expenses for the period', function (): void {
    $report = app(ProfitAndLossCalculator::class)->forBranch($this->branch->id, Carbon::parse('2026-07-01'), Carbon::parse('2026-07-31'));

    expect($report['total_revenue_cents'])->toBe(5000)
        ->and($report['total_expense_cents'])->toBe(2000)
        ->and($report['net_income_cents'])->toBe(3000);
});

test('balance sheet reports assets, liabilities, and equity as of a date', function (): void {
    $report = app(BalanceSheetCalculator::class)->forBranch($this->branch->id, Carbon::parse('2026-07-31'));

    expect($report['total_assets_cents'])->toBe(13000)
        ->and($report['total_liabilities_cents'])->toBe(0)
        ->and($report['total_equity_cents'])->toBe(10000);
});
