<?php

declare(strict_types=1);

use App\Domain\Reservation\Actions\CancelReservationAction;
use App\Domain\Reservation\Enums\ReservationStatus;
use App\Models\Branch;
use App\Models\Reservation;
use App\Models\ReservationRoom;
use App\Models\RoomType;
use App\Models\User;
use Illuminate\Validation\ValidationException;

test('cancelling within the free window charges no fee', function (): void {
    $branch = Branch::factory()->create(['cancellation_policy' => ['free_cancellation_hours' => 48, 'fee_percent' => 50]]);
    $reservation = Reservation::factory()->create([
        'branch_id' => $branch->id,
        'status' => ReservationStatus::Confirmed,
        'arrival_date' => now()->addDays(10),
        'departure_date' => now()->addDays(12),
    ]);
    $user = User::factory()->create();

    $result = app(CancelReservationAction::class)->handle($reservation, $user, 'Change of plans');

    expect($result->status)->toBe(ReservationStatus::Cancelled)
        ->and($result->cancellation_fee_cents)->toBe(0)
        ->and($result->statusLogs()->latest('id')->first()->reason)->toBe('Change of plans');
});

test('cancelling inside the free-cancellation window charges the configured fee percentage', function (): void {
    $branch = Branch::factory()->create(['cancellation_policy' => ['free_cancellation_hours' => 48, 'fee_percent' => 50]]);
    $roomType = RoomType::factory()->create(['branch_id' => $branch->id]);
    $reservation = Reservation::factory()->create([
        'branch_id' => $branch->id,
        'status' => ReservationStatus::Confirmed,
        'arrival_date' => now()->addHours(10),
        'departure_date' => now()->addHours(10)->addDays(2),
    ]);
    ReservationRoom::factory()->create([
        'reservation_id' => $reservation->id,
        'room_type_id' => $roomType->id,
        'rate_cents' => 10000,
    ]);
    $user = User::factory()->create();

    $result = app(CancelReservationAction::class)->handle($reservation, $user);

    // 2 nights * $100 = $200 total; 50% fee = $100 = 10000 cents.
    expect($result->cancellation_fee_cents)->toBe(10000);
});

test('a checked-in reservation cannot be cancelled', function (): void {
    $reservation = Reservation::factory()->checkedIn()->create();
    $user = User::factory()->create();

    app(CancelReservationAction::class)->handle($reservation, $user);
})->throws(ValidationException::class);

test('an already cancelled reservation cannot be cancelled again', function (): void {
    $reservation = Reservation::factory()->cancelled()->create();
    $user = User::factory()->create();

    app(CancelReservationAction::class)->handle($reservation, $user);
})->throws(ValidationException::class);
