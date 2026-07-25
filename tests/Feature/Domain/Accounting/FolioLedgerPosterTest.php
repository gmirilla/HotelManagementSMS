<?php

declare(strict_types=1);

use App\Domain\Accounting\Support\FolioLedgerPoster;
use App\Domain\Accounting\Support\TrialBalanceCalculator;
use App\Domain\FrontDesk\Actions\CheckInGuestAction;
use App\Domain\FrontDesk\Enums\ChargeType;
use App\Domain\Payment\Actions\RecordFolioPaymentAction;
use App\Domain\Reservation\Enums\ReservationStatus;
use App\Domain\Room\Enums\RoomStatus;
use App\Models\Account;
use App\Models\Branch;
use App\Models\Folio;
use App\Models\JournalEntry;
use App\Models\Payment;
use App\Models\Reservation;
use App\Models\ReservationRoom;
use App\Models\Room;
use App\Models\RoomType;
use App\Models\User;

function makeFolioBranch(): Branch
{
    $branch = Branch::factory()->create();
    seedChartOfAccounts($branch);

    return $branch;
}

function accountByCode(Branch $branch, string $code): Account
{
    return Account::where('branch_id', $branch->id)->where('code', $code)->firstOrFail();
}

test('posting a room charge debits Accounts Receivable and credits Room Revenue', function (): void {
    $branch = makeFolioBranch();
    $folio = Folio::factory()->create(['branch_id' => $branch->id]);
    $charge = $folio->charges()->create(['charge_type' => ChargeType::Room, 'description' => 'Room charge', 'amount_cents' => 20000, 'charge_date' => now()->toDateString()]);
    $charge->setRelation('folio', $folio);

    app(FolioLedgerPoster::class)->postCharge($charge, null);

    $entry = JournalEntry::where('reference_type', $charge->getMorphClass())->where('reference_id', $charge->id)->firstOrFail();

    expect($entry->isBalanced())->toBeTrue()
        ->and($entry->totalDebitCents())->toBe(20000)
        ->and($entry->lines()->where('account_id', accountByCode($branch, '1100')->id)->where('side', 'debit')->where('amount_cents', 20000)->exists())->toBeTrue()
        ->and($entry->lines()->where('account_id', accountByCode($branch, '4000')->id)->where('side', 'credit')->where('amount_cents', 20000)->exists())->toBeTrue();
});

test('posting a tax charge credits Taxes Payable, not revenue', function (): void {
    $branch = makeFolioBranch();
    $folio = Folio::factory()->create(['branch_id' => $branch->id]);
    $charge = $folio->charges()->create(['charge_type' => ChargeType::Tax, 'description' => 'VAT', 'amount_cents' => 1500, 'charge_date' => now()->toDateString()]);
    $charge->setRelation('folio', $folio);

    app(FolioLedgerPoster::class)->postCharge($charge, null);

    $entry = JournalEntry::where('reference_type', $charge->getMorphClass())->where('reference_id', $charge->id)->firstOrFail();

    expect($entry->isBalanced())->toBeTrue()
        ->and($entry->lines()->where('account_id', accountByCode($branch, '2100')->id)->where('side', 'credit')->where('amount_cents', 1500)->exists())->toBeTrue();
});

test('posting a restaurant charge credits Restaurant Revenue', function (): void {
    $branch = makeFolioBranch();
    $folio = Folio::factory()->create(['branch_id' => $branch->id]);
    $charge = $folio->charges()->create(['charge_type' => ChargeType::Restaurant, 'description' => 'Room service', 'amount_cents' => 2500, 'charge_date' => now()->toDateString()]);
    $charge->setRelation('folio', $folio);

    app(FolioLedgerPoster::class)->postCharge($charge, null);

    $entry = JournalEntry::where('reference_type', $charge->getMorphClass())->where('reference_id', $charge->id)->firstOrFail();

    expect($entry->lines()->where('account_id', accountByCode($branch, '4100')->id)->where('side', 'credit')->where('amount_cents', 2500)->exists())->toBeTrue();
});

test('posting a negative reversal charge swaps the debit and credit sides', function (): void {
    $branch = makeFolioBranch();
    $folio = Folio::factory()->create(['branch_id' => $branch->id]);
    $charge = $folio->charges()->create(['charge_type' => ChargeType::Room, 'description' => 'Reversal of prior nights', 'amount_cents' => -30000, 'charge_date' => now()->toDateString()]);
    $charge->setRelation('folio', $folio);

    app(FolioLedgerPoster::class)->postCharge($charge, null);

    $entry = JournalEntry::where('reference_type', $charge->getMorphClass())->where('reference_id', $charge->id)->firstOrFail();

    expect($entry->isBalanced())->toBeTrue()
        ->and($entry->lines()->where('account_id', accountByCode($branch, '1100')->id)->where('side', 'credit')->where('amount_cents', 30000)->exists())->toBeTrue()
        ->and($entry->lines()->where('account_id', accountByCode($branch, '4000')->id)->where('side', 'debit')->where('amount_cents', 30000)->exists())->toBeTrue();
});

test('posting a payment debits Cash and credits Accounts Receivable', function (): void {
    $branch = makeFolioBranch();
    $folio = Folio::factory()->create(['branch_id' => $branch->id]);
    $payment = Payment::factory()->create(['branch_id' => $branch->id, 'folio_id' => $folio->id, 'amount_cents' => 20000]);

    app(FolioLedgerPoster::class)->postPayment($payment, null);

    $entry = JournalEntry::where('reference_type', $payment->getMorphClass())->where('reference_id', $payment->id)->firstOrFail();

    expect($entry->isBalanced())->toBeTrue()
        ->and($entry->lines()->where('account_id', accountByCode($branch, '1000')->id)->where('side', 'debit')->where('amount_cents', 20000)->exists())->toBeTrue()
        ->and($entry->lines()->where('account_id', accountByCode($branch, '1100')->id)->where('side', 'credit')->where('amount_cents', 20000)->exists())->toBeTrue();
});

test('posting a refund debits Accounts Receivable and credits Cash', function (): void {
    $branch = makeFolioBranch();
    $folio = Folio::factory()->create(['branch_id' => $branch->id]);
    $payment = Payment::factory()->create(['branch_id' => $branch->id, 'folio_id' => $folio->id, 'amount_cents' => 20000]);

    app(FolioLedgerPoster::class)->postRefund($payment, null);

    $entry = JournalEntry::where('reference_type', $payment->getMorphClass())->where('reference_id', $payment->id)->firstOrFail();

    expect($entry->isBalanced())->toBeTrue()
        ->and($entry->lines()->where('account_id', accountByCode($branch, '1100')->id)->where('side', 'debit')->where('amount_cents', 20000)->exists())->toBeTrue()
        ->and($entry->lines()->where('account_id', accountByCode($branch, '1000')->id)->where('side', 'credit')->where('amount_cents', 20000)->exists())->toBeTrue();
});

test('checking in a guest and paying the folio in full leaves the trial balance balanced', function (): void {
    $branch = makeFolioBranch();
    $roomType = RoomType::factory()->create(['branch_id' => $branch->id, 'base_rate_cents' => 10000]);
    $room = Room::factory()->create(['branch_id' => $branch->id, 'room_type_id' => $roomType->id, 'status' => RoomStatus::VacantClean]);
    $reservation = Reservation::factory()->create([
        'branch_id' => $branch->id,
        'status' => ReservationStatus::Confirmed,
        'arrival_date' => now(),
        'departure_date' => now()->addDays(2),
    ]);
    ReservationRoom::factory()->create([
        'reservation_id' => $reservation->id,
        'room_type_id' => $roomType->id,
        'room_id' => null,
        'rate_cents' => 10000,
    ]);
    $staff = User::factory()->create();

    $folio = app(CheckInGuestAction::class)->handle($reservation, $room, $staff);
    expect($folio->balance_cents)->toBe(20000);

    app(RecordFolioPaymentAction::class)->handle($folio, 'cash', 20000, $staff);
    expect($folio->fresh()->balance_cents)->toBe(0);

    $trialBalance = app(TrialBalanceCalculator::class)->forBranch($branch->id, now()->subDay(), now()->addDay());

    $totalDebits = $trialBalance->sum('debit_cents');
    $totalCredits = $trialBalance->sum('credit_cents');
    expect($totalDebits)->toBe($totalCredits);

    $byCode = $trialBalance->keyBy(fn (array $row): string => $row['account']->code);
    expect($byCode['1000']['debit_cents'])->toBe(20000)
        ->and($byCode['1100']['debit_cents'])->toBe(0)
        ->and($byCode['1100']['credit_cents'])->toBe(0)
        ->and($byCode['4000']['credit_cents'])->toBe(20000);
});

test('posting a charge on a branch with no chart of accounts throws a clear error', function (): void {
    $branch = Branch::factory()->create();
    $folio = Folio::factory()->create(['branch_id' => $branch->id]);
    $charge = $folio->charges()->create(['charge_type' => ChargeType::Room, 'description' => 'Room charge', 'amount_cents' => 20000, 'charge_date' => now()->toDateString()]);
    $charge->setRelation('folio', $folio);

    app(FolioLedgerPoster::class)->postCharge($charge, null);
})->throws(RuntimeException::class, 'has no chart-of-accounts entry');
