<?php

declare(strict_types=1);

use App\Domain\FrontDesk\Actions\CheckInGuestAction;
use App\Domain\FrontDesk\Actions\CheckOutGuestAction;
use App\Domain\FrontDesk\Actions\PostFolioChargeAction;
use App\Domain\Payment\Actions\RecordFolioPaymentAction;
use App\Domain\Reservation\Enums\ReservationStatus;
use App\Domain\Room\Enums\RoomStatus;
use App\Models\Branch;
use App\Models\JournalEntry;
use App\Models\Reservation;
use App\Models\ReservationRoom;
use App\Models\Room;
use App\Models\RoomType;
use App\Models\User;
use Illuminate\Validation\ValidationException;

function makeReadyReservation(int $nights = 2): array
{
    $branch = Branch::factory()->create();
    seedChartOfAccounts($branch);
    $roomType = RoomType::factory()->create(['branch_id' => $branch->id, 'base_rate_cents' => 10000]);
    $room = Room::factory()->create(['branch_id' => $branch->id, 'room_type_id' => $roomType->id, 'status' => RoomStatus::VacantClean]);
    $reservation = Reservation::factory()->create([
        'branch_id' => $branch->id,
        'status' => ReservationStatus::Confirmed,
        'arrival_date' => now(),
        'departure_date' => now()->addDays($nights),
    ]);
    ReservationRoom::factory()->create([
        'reservation_id' => $reservation->id,
        'room_type_id' => $roomType->id,
        'room_id' => null,
        'rate_cents' => 10000,
    ]);

    return [$reservation, $room];
}

test('checking in assigns the room, opens a pre-charged folio, and occupies the room', function (): void {
    [$reservation, $room] = makeReadyReservation(nights: 2);
    $staff = User::factory()->create();

    $folio = app(CheckInGuestAction::class)->handle($reservation, $room, $staff);

    expect($reservation->fresh()->status)->toBe(ReservationStatus::CheckedIn)
        ->and($room->fresh()->status)->toBe(RoomStatus::Occupied)
        ->and($folio->charges)->toHaveCount(2)
        ->and($folio->balance_cents)->toBe(20000)
        ->and($reservation->fresh()->rooms->first()->room_id)->toBe($room->id);

    $entries = JournalEntry::whereIn('reference_id', $folio->charges->pluck('id'))->where('reference_type', $folio->charges->first()->getMorphClass())->get();
    expect($entries)->toHaveCount(2)
        ->and($entries->every(fn (JournalEntry $entry): bool => $entry->isBalanced()))->toBeTrue()
        ->and($entries->sum(fn (JournalEntry $entry): int => $entry->totalDebitCents()))->toBe(20000);
});

test('a room mismatched to the booked room type cannot be used for check-in', function (): void {
    [$reservation] = makeReadyReservation();
    $otherRoomType = RoomType::factory()->create(['branch_id' => $reservation->branch_id]);
    $wrongRoom = Room::factory()->create(['branch_id' => $reservation->branch_id, 'room_type_id' => $otherRoomType->id]);
    $staff = User::factory()->create();

    app(CheckInGuestAction::class)->handle($reservation, $wrongRoom, $staff);
})->throws(ValidationException::class);

test('an occupied room cannot be assigned at check-in', function (): void {
    [$reservation, $room] = makeReadyReservation();
    $room->update(['status' => RoomStatus::Occupied]);
    $staff = User::factory()->create();

    app(CheckInGuestAction::class)->handle($reservation, $room, $staff);
})->throws(ValidationException::class);

test('checking out with a zero balance closes the folio and marks the room dirty', function (): void {
    [$reservation, $room] = makeReadyReservation(nights: 1);
    $staff = User::factory()->create();

    $folio = app(CheckInGuestAction::class)->handle($reservation, $room, $staff);
    app(RecordFolioPaymentAction::class)->handle($folio, 'cash', $folio->balance_cents, $staff);

    app(CheckOutGuestAction::class)->handle($reservation->fresh(), $staff);

    expect($reservation->fresh()->status)->toBe(ReservationStatus::CheckedOut)
        ->and($room->fresh()->status)->toBe(RoomStatus::VacantDirty)
        ->and($folio->fresh()->status->value)->toBe('closed');
});

test('checking out with an outstanding balance is refused unless forced', function (): void {
    [$reservation, $room] = makeReadyReservation(nights: 1);
    $staff = User::factory()->create();

    app(CheckInGuestAction::class)->handle($reservation, $room, $staff);

    app(CheckOutGuestAction::class)->handle($reservation->fresh(), $staff);
})->throws(ValidationException::class);

test('forcing checkout bypasses the outstanding-balance guard', function (): void {
    [$reservation, $room] = makeReadyReservation(nights: 1);
    $staff = User::factory()->create();

    app(CheckInGuestAction::class)->handle($reservation, $room, $staff);

    $result = app(CheckOutGuestAction::class)->handle($reservation->fresh(), $staff, force: true);

    expect($result->status)->toBe(ReservationStatus::CheckedOut);
});

test('posting an extra charge and a payment keeps the folio balance correct', function (): void {
    [$reservation, $room] = makeReadyReservation(nights: 1);
    $staff = User::factory()->create();

    $folio = app(CheckInGuestAction::class)->handle($reservation, $room, $staff);
    expect($folio->balance_cents)->toBe(10000);

    app(PostFolioChargeAction::class)->handle($folio, 'restaurant', 'Room service', 2500, $staff);
    expect($folio->fresh()->balance_cents)->toBe(12500);

    $payment = app(RecordFolioPaymentAction::class)->handle($folio, 'cash', 12500, $staff);
    expect($folio->fresh()->balance_cents)->toBe(0);

    $paymentEntry = JournalEntry::where('reference_type', $payment->getMorphClass())->where('reference_id', $payment->id)->firstOrFail();
    expect($paymentEntry->isBalanced())->toBeTrue()
        ->and($paymentEntry->totalDebitCents())->toBe(12500);
});
