<?php

declare(strict_types=1);

namespace App\Domain\Accounting\Actions;

use App\Domain\Accounting\Enums\AccountType;
use App\Models\Account;
use App\Models\Branch;

/**
 * Gives every new branch a working chart of accounts from the moment it's
 * created (FR-ACC-001), so postings from FolioLedgerPoster/CorporateLedgerPoster
 * never hit a branch with no accounts to resolve. Mirrors the chart
 * HotelDemoSeeder::seedAccounting() creates for demo branches, minus its
 * opening/demo journal entries — just the accounts, nothing posted yet.
 */
class SeedDefaultChartOfAccountsAction
{
    public function handle(Branch $branch): void
    {
        $accounts = [
            ['code' => '1000', 'name' => 'Cash', 'account_type' => AccountType::Asset],
            ['code' => '1100', 'name' => 'Accounts Receivable', 'account_type' => AccountType::Asset],
            ['code' => '1200', 'name' => 'Inventory', 'account_type' => AccountType::Asset],
            ['code' => '2000', 'name' => 'Accounts Payable', 'account_type' => AccountType::Liability],
            ['code' => '2100', 'name' => 'Taxes Payable', 'account_type' => AccountType::Liability],
            ['code' => '3000', 'name' => "Owner's Equity", 'account_type' => AccountType::Equity],
            ['code' => '4000', 'name' => 'Room Revenue', 'account_type' => AccountType::Revenue],
            ['code' => '4100', 'name' => 'Restaurant Revenue', 'account_type' => AccountType::Revenue],
            ['code' => '5000', 'name' => 'Cost of Goods Sold', 'account_type' => AccountType::Expense],
            ['code' => '5100', 'name' => 'Payroll Expense', 'account_type' => AccountType::Expense],
            ['code' => '5200', 'name' => 'Utilities Expense', 'account_type' => AccountType::Expense],
            ['code' => '5300', 'name' => 'Maintenance Expense', 'account_type' => AccountType::Expense],
        ];

        foreach ($accounts as $account) {
            Account::create([...$account, 'branch_id' => $branch->id]);
        }
    }
}
