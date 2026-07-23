<?php

declare(strict_types=1);

/**
 * Full-page Livewire render tests for every Accounting component, in both
 * empty and populated states. These exist because Action/Calculator unit
 * tests never would have caught the class of bug found in the Restaurant/
 * Inventory modules (a #[Computed] method declared to return one Collection
 * type but returning another at runtime, which only blows up on an actual
 * render) — see RestaurantLivewireRenderTest.php / InventoryLivewireRenderTest.php
 * for the original incident this practice comes from.
 */

use App\Domain\Accounting\Actions\PostJournalEntryAction;
use App\Domain\Accounting\Enums\AccountType;
use App\Domain\Accounting\Enums\ApStatus;
use App\Domain\Accounting\Enums\ArStatus;
use App\Livewire\Accounting\ArApManager;
use App\Livewire\Accounting\Cashbook;
use App\Livewire\Accounting\ChartOfAccounts;
use App\Livewire\Accounting\FinancialReports;
use App\Livewire\Accounting\JournalEntryManager;
use App\Livewire\Accounting\TaxRuleManager;
use App\Models\Account;
use App\Models\ApEntry;
use App\Models\ArEntry;
use App\Models\Branch;
use App\Models\CashbookEntry;
use App\Models\CorporateAccount;
use App\Models\Supplier;
use App\Models\TaxRule;
use App\Models\User;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

beforeEach(function (): void {
    Permission::firstOrCreate(['name' => 'accounting.manage', 'guard_name' => 'web']);
    $role = Role::firstOrCreate(['name' => 'Accountant', 'guard_name' => 'web']);
    $role->givePermissionTo('accounting.manage');

    $this->branch = Branch::factory()->create();
    $this->staff = User::factory()->create(['tenant_id' => $this->branch->tenant_id, 'current_branch_id' => $this->branch->id]);
    $this->staff->assignRole($role);
    $this->branch->staff()->attach($this->staff->id, ['role_id' => $role->id, 'is_primary' => true]);
});

test('chart of accounts renders with no accounts yet', function (): void {
    Livewire::actingAs($this->staff)->test(ChartOfAccounts::class)->assertOk();
});

test('chart of accounts renders with accounts present', function (): void {
    Account::factory()->create(['branch_id' => $this->branch->id, 'account_type' => AccountType::Asset]);
    Account::factory()->create(['branch_id' => $this->branch->id, 'account_type' => AccountType::Revenue]);

    Livewire::actingAs($this->staff)->test(ChartOfAccounts::class)->assertOk();
});

test('journal entry manager renders with no entries yet', function (): void {
    Livewire::actingAs($this->staff)->test(JournalEntryManager::class)->assertOk();
});

test('journal entry manager renders with posted entries present', function (): void {
    $cash = Account::factory()->create(['branch_id' => $this->branch->id, 'account_type' => AccountType::Asset]);
    $revenue = Account::factory()->create(['branch_id' => $this->branch->id, 'account_type' => AccountType::Revenue]);

    app(PostJournalEntryAction::class)->handle(
        branchId: $this->branch->id,
        entryDate: now(),
        lines: [
            ['account_id' => $cash->id, 'side' => 'debit', 'amount_cents' => 5000],
            ['account_id' => $revenue->id, 'side' => 'credit', 'amount_cents' => 5000],
        ],
        memo: 'Test posting',
        createdBy: $this->staff,
    );

    Livewire::actingAs($this->staff)->test(JournalEntryManager::class)->assertOk();
});

test('financial reports render across all three tabs with no data yet', function (): void {
    $component = Livewire::actingAs($this->staff)->test(FinancialReports::class)->assertOk();

    $component->set('tab', 'profit_loss')->assertOk();
    $component->set('tab', 'balance_sheet')->assertOk();
});

test('financial reports render across all three tabs with posted entries present', function (): void {
    $cash = Account::factory()->create(['branch_id' => $this->branch->id, 'account_type' => AccountType::Asset]);
    $revenue = Account::factory()->create(['branch_id' => $this->branch->id, 'account_type' => AccountType::Revenue]);

    app(PostJournalEntryAction::class)->handle(
        branchId: $this->branch->id,
        entryDate: now(),
        lines: [
            ['account_id' => $cash->id, 'side' => 'debit', 'amount_cents' => 5000],
            ['account_id' => $revenue->id, 'side' => 'credit', 'amount_cents' => 5000],
        ],
    );

    $component = Livewire::actingAs($this->staff)->test(FinancialReports::class)->assertOk();

    $component->set('tab', 'profit_loss')->assertOk();
    $component->set('tab', 'balance_sheet')->assertOk();
});

test('cashbook renders with no entries yet', function (): void {
    Livewire::actingAs($this->staff)->test(Cashbook::class)->assertOk();
});

test('cashbook renders and finds entries for today\'s shift date', function (): void {
    CashbookEntry::factory()->create(['branch_id' => $this->branch->id, 'cashier_user_id' => $this->staff->id, 'shift_date' => now()->toDateString()]);

    $component = Livewire::actingAs($this->staff)->test(Cashbook::class)->assertOk();

    // Regression guard: shift_date is an Eloquent 'date' cast column, which
    // serializes to a full "Y-m-d H:i:s" string in the database, so an exact
    // string-equality where() clause against a "Y-m-d" input never matches.
    expect($component->get('entries'))->toHaveCount(1);
});

test('ar/ap manager renders with no entries yet', function (): void {
    Livewire::actingAs($this->staff)->test(ArApManager::class)->assertOk();
});

test('ar/ap manager renders receivables and payables tabs with entries present', function (): void {
    $corporateAccount = CorporateAccount::factory()->create(['tenant_id' => $this->branch->tenant_id]);
    $supplier = Supplier::factory()->create(['tenant_id' => $this->branch->tenant_id]);

    ArEntry::factory()->create(['branch_id' => $this->branch->id, 'corporate_account_id' => $corporateAccount->id, 'status' => ArStatus::Open]);
    ApEntry::factory()->create(['branch_id' => $this->branch->id, 'supplier_id' => $supplier->id, 'status' => ApStatus::Open]);

    $component = Livewire::actingAs($this->staff)->test(ArApManager::class)->assertOk();

    $component->set('tab', 'payables')->assertOk();
});

test('tax rule manager renders with no rules yet', function (): void {
    Livewire::actingAs($this->staff)->test(TaxRuleManager::class)->assertOk();
});

test('tax rule manager renders with rules present', function (): void {
    TaxRule::factory()->create(['branch_id' => $this->branch->id]);

    Livewire::actingAs($this->staff)->test(TaxRuleManager::class)->assertOk();
});
