<?php

declare(strict_types=1);

use App\Domain\Accounting\Support\CorporateLedgerPoster;
use App\Models\Account;
use App\Models\ApEntry;
use App\Models\ArEntry;
use App\Models\Branch;
use App\Models\JournalEntry;

function makeCorporateBranch(): Branch
{
    $branch = Branch::factory()->create();
    seedChartOfAccounts($branch);

    return $branch;
}

function corporateAccountByCode(Branch $branch, string $code): Account
{
    return Account::where('branch_id', $branch->id)->where('code', $code)->firstOrFail();
}

test('posting an AR payment debits Cash and credits Accounts Receivable', function (): void {
    $branch = makeCorporateBranch();
    $entry = ArEntry::factory()->create(['branch_id' => $branch->id]);

    app(CorporateLedgerPoster::class)->postArPayment($entry, 5000, null);

    $ledgerEntry = JournalEntry::where('reference_type', $entry->getMorphClass())->where('reference_id', $entry->id)->firstOrFail();

    expect($ledgerEntry->isBalanced())->toBeTrue()
        ->and($ledgerEntry->lines()->where('account_id', corporateAccountByCode($branch, '1000')->id)->where('side', 'debit')->where('amount_cents', 5000)->exists())->toBeTrue()
        ->and($ledgerEntry->lines()->where('account_id', corporateAccountByCode($branch, '1100')->id)->where('side', 'credit')->where('amount_cents', 5000)->exists())->toBeTrue();
});

test('posting an AP payment debits Accounts Payable and credits Cash', function (): void {
    $branch = makeCorporateBranch();
    $entry = ApEntry::factory()->create(['branch_id' => $branch->id]);

    app(CorporateLedgerPoster::class)->postApPayment($entry, 3000, null);

    $ledgerEntry = JournalEntry::where('reference_type', $entry->getMorphClass())->where('reference_id', $entry->id)->firstOrFail();

    expect($ledgerEntry->isBalanced())->toBeTrue()
        ->and($ledgerEntry->lines()->where('account_id', corporateAccountByCode($branch, '2000')->id)->where('side', 'debit')->where('amount_cents', 3000)->exists())->toBeTrue()
        ->and($ledgerEntry->lines()->where('account_id', corporateAccountByCode($branch, '1000')->id)->where('side', 'credit')->where('amount_cents', 3000)->exists())->toBeTrue();
});

test('posting an AR payment on a branch with no chart of accounts throws a clear error', function (): void {
    $branch = Branch::factory()->create();
    $entry = ArEntry::factory()->create(['branch_id' => $branch->id]);

    app(CorporateLedgerPoster::class)->postArPayment($entry, 5000, null);
})->throws(RuntimeException::class, 'has no chart-of-accounts entry');
