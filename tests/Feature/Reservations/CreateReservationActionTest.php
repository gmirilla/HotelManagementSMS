<?php

declare(strict_types=1);

use App\Domain\Reservation\Actions\CreateReservationAction;
use App\Domain\Reservation\Enums\ReservationStatus;
use App\Models\Branch;
use App\Models\Guest;
use App\Models\Room;
use App\Models\RoomType;
use Illuminate\Support\Carbon;
use Illuminate\Validation\ValidationException;

test('it creates a confirmed reservation with a room-type line item', function (): void {
    $branch = Branch::factory()->create();
    $roomType = RoomType::factory()->create(['branch_id' => $branch->id, 'base_rate_cents' => 12000]);
    Room::factory()->create(['branch_id' => $branch->id, 'room_type_id' => $roomType->id]);
    $guest = Guest::factory()->create(['tenant_id' => $branch->tenant_id]);

    $reservation = app(CreateReservationAction::class)->handle(
        branchId: $branch->id,
        guestId: $guest->id,
        roomType: $roomType,
        arrival: Carbon::parse('2026-06-01'),
        departure: Carbon::parse('2026-06-03'),
        adults: 2,
        children: 0,
        source: 'walk_in',
    );

    expect($reservation->status)->toBe(ReservationStatus::Confirmed)
        ->and($reservation->confirmation_code)->toHaveLength(8)
        ->and($reservation->rooms)->toHaveCount(1)
        ->and($reservation->rooms->first()->rate_cents)->toBe(12000)
        ->and($reservation->statusLogs)->toHaveCount(1);
});

test('it refuses to book a room type with no availability', function (): void {
    $branch = Branch::factory()->create();
    $roomType = RoomType::factory()->create(['branch_id' => $branch->id]);
    // No rooms created — zero inventory.
    $guest = Guest::factory()->create(['tenant_id' => $branch->tenant_id]);

    app(CreateReservationAction::class)->handle(
        branchId: $branch->id,
        guestId: $guest->id,
        roomType: $roomType,
        arrival: Carbon::parse('2026-06-01'),
        departure: Carbon::parse('2026-06-03'),
        adults: 1,
        children: 0,
        source: 'walk_in',
    );
})->throws(ValidationException::class);

test('it rejects a departure date on or before the arrival date', function (): void {
    $branch = Branch::factory()->create();
    $roomType = RoomType::factory()->create(['branch_id' => $branch->id]);
    Room::factory()->create(['branch_id' => $branch->id, 'room_type_id' => $roomType->id]);
    $guest = Guest::factory()->create(['tenant_id' => $branch->tenant_id]);

    app(CreateReservationAction::class)->handle(
        branchId: $branch->id,
        guestId: $guest->id,
        roomType: $roomType,
        arrival: Carbon::parse('2026-06-03'),
        departure: Carbon::parse('2026-06-01'),
        adults: 1,
        children: 0,
        source: 'walk_in',
    );
})->throws(ValidationException::class);
