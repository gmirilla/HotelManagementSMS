<?php

declare(strict_types=1);

use App\Domain\Accounting\Actions\PostJournalEntryAction;
use App\Domain\Accounting\Actions\RecordApPaymentAction;
use App\Domain\Accounting\Actions\RecordArPaymentAction;
use App\Domain\Accounting\Enums\AccountType;
use App\Domain\Accounting\Enums\ApStatus;
use App\Domain\Accounting\Enums\ArStatus;
use App\Models\Account;
use App\Models\ApEntry;
use App\Models\ArEntry;
use App\Models\Branch;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Validation\ValidationException;

test('posting a balanced journal entry creates the entry and its lines', function (): void {
    $branch = Branch::factory()->create();
    $cash = Account::factory()->create(['branch_id' => $branch->id, 'account_type' => AccountType::Asset]);
    $revenue = Account::factory()->create(['branch_id' => $branch->id, 'account_type' => AccountType::Revenue]);
    $staff = User::factory()->create(['tenant_id' => $branch->tenant_id]);

    $entry = app(PostJournalEntryAction::class)->handle(
        branchId: $branch->id,
        entryDate: Carbon::parse('2026-07-01'),
        lines: [
            ['account_id' => $cash->id, 'side' => 'debit', 'amount_cents' => 10000],
            ['account_id' => $revenue->id, 'side' => 'credit', 'amount_cents' => 10000],
        ],
        memo: 'Cash sale',
        createdBy: $staff,
    );

    expect($entry->lines()->count())->toBe(2)
        ->and($entry->isBalanced())->toBeTrue()
        ->and($entry->totalDebitCents())->toBe(10000)
        ->and($entry->totalCreditCents())->toBe(10000);
});

test('an unbalanced journal entry is rejected', function (): void {
    $branch = Branch::factory()->create();
    $cash = Account::factory()->create(['branch_id' => $branch->id]);
    $revenue = Account::factory()->create(['branch_id' => $branch->id]);

    app(PostJournalEntryAction::class)->handle(
        branchId: $branch->id,
        entryDate: now(),
        lines: [
            ['account_id' => $cash->id, 'side' => 'debit', 'amount_cents' => 10000],
            ['account_id' => $revenue->id, 'side' => 'credit', 'amount_cents' => 9000],
        ],
    );
})->throws(ValidationException::class);

test('a journal entry needs at least two lines', function (): void {
    $branch = Branch::factory()->create();
    $cash = Account::factory()->create(['branch_id' => $branch->id]);

    app(PostJournalEntryAction::class)->handle(
        branchId: $branch->id,
        entryDate: now(),
        lines: [
            ['account_id' => $cash->id, 'side' => 'debit', 'amount_cents' => 10000],
        ],
    );
})->throws(ValidationException::class);

test('a journal line amount must be greater than zero', function (): void {
    $branch = Branch::factory()->create();
    $cash = Account::factory()->create(['branch_id' => $branch->id]);
    $revenue = Account::factory()->create(['branch_id' => $branch->id]);

    app(PostJournalEntryAction::class)->handle(
        branchId: $branch->id,
        entryDate: now(),
        lines: [
            ['account_id' => $cash->id, 'side' => 'debit', 'amount_cents' => 0],
            ['account_id' => $revenue->id, 'side' => 'credit', 'amount_cents' => 0],
        ],
    );
})->throws(ValidationException::class);

test('recording a partial AR payment flips status to partially paid', function (): void {
    $entry = ArEntry::factory()->create(['amount_cents' => 10000, 'paid_cents' => 0, 'status' => ArStatus::Open]);

    app(RecordArPaymentAction::class)->handle($entry, 4000);

    expect($entry->fresh()->paid_cents)->toBe(4000)
        ->and($entry->fresh()->status)->toBe(ArStatus::PartiallyPaid)
        ->and($entry->fresh()->outstandingCents())->toBe(6000);
});

test('recording a full AR payment flips status to paid', function (): void {
    $entry = ArEntry::factory()->create(['amount_cents' => 10000, 'paid_cents' => 0, 'status' => ArStatus::Open]);

    app(RecordArPaymentAction::class)->handle($entry, 10000);

    expect($entry->fresh()->status)->toBe(ArStatus::Paid)
        ->and($entry->fresh()->outstandingCents())->toBe(0);
});

test('an AR payment exceeding the outstanding balance is rejected', function (): void {
    $entry = ArEntry::factory()->create(['amount_cents' => 10000, 'paid_cents' => 0, 'status' => ArStatus::Open]);

    app(RecordArPaymentAction::class)->handle($entry, 10001);
})->throws(ValidationException::class);

test('a written-off receivable cannot receive payments', function (): void {
    $entry = ArEntry::factory()->create(['amount_cents' => 10000, 'paid_cents' => 0, 'status' => ArStatus::WrittenOff]);

    app(RecordArPaymentAction::class)->handle($entry, 100);
})->throws(ValidationException::class);

test('recording a full AP payment flips status to paid', function (): void {
    $entry = ApEntry::factory()->create(['amount_cents' => 8000, 'paid_cents' => 0, 'status' => ApStatus::Open]);

    app(RecordApPaymentAction::class)->handle($entry, 8000);

    expect($entry->fresh()->status)->toBe(ApStatus::Paid)
        ->and($entry->fresh()->outstandingCents())->toBe(0);
});

test('a disputed payable cannot be paid', function (): void {
    $entry = ApEntry::factory()->create(['amount_cents' => 8000, 'paid_cents' => 0, 'status' => ApStatus::Disputed]);

    app(RecordApPaymentAction::class)->handle($entry, 100);
})->throws(ValidationException::class);
