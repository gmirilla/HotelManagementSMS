<?php

declare(strict_types=1);

use App\Domain\Reservation\Support\AvailabilityChecker;
use App\Models\Branch;
use App\Models\Reservation;
use App\Models\ReservationRoom;
use App\Models\Room;
use App\Models\RoomType;
use Illuminate\Support\Carbon;

test('all rooms of a type are available when there are no overlapping reservations', function (): void {
    $branch = Branch::factory()->create();
    $roomType = RoomType::factory()->create(['branch_id' => $branch->id]);
    Room::factory(3)->create(['branch_id' => $branch->id, 'room_type_id' => $roomType->id]);

    $checker = app(AvailabilityChecker::class);

    expect($checker->availableRoomCount($roomType, Carbon::parse('2026-04-01'), Carbon::parse('2026-04-03')))->toBe(3);
});

test('overlapping reservations reduce the available count even without a physically assigned room', function (): void {
    $branch = Branch::factory()->create();
    $roomType = RoomType::factory()->create(['branch_id' => $branch->id]);
    Room::factory(2)->create(['branch_id' => $branch->id, 'room_type_id' => $roomType->id]);

    $reservation = Reservation::factory()->create([
        'branch_id' => $branch->id,
        'status' => 'confirmed',
        'arrival_date' => '2026-04-01',
        'departure_date' => '2026-04-03',
    ]);
    ReservationRoom::factory()->create([
        'reservation_id' => $reservation->id,
        'room_type_id' => $roomType->id,
        'room_id' => null,
    ]);

    $checker = app(AvailabilityChecker::class);

    expect($checker->availableRoomCount($roomType, Carbon::parse('2026-04-01'), Carbon::parse('2026-04-03')))->toBe(1);
});

test('a reservation entirely outside the requested date range does not reduce availability', function (): void {
    $branch = Branch::factory()->create();
    $roomType = RoomType::factory()->create(['branch_id' => $branch->id]);
    Room::factory(1)->create(['branch_id' => $branch->id, 'room_type_id' => $roomType->id]);

    $reservation = Reservation::factory()->create([
        'branch_id' => $branch->id,
        'status' => 'confirmed',
        'arrival_date' => '2026-05-01',
        'departure_date' => '2026-05-03',
    ]);
    ReservationRoom::factory()->create(['reservation_id' => $reservation->id, 'room_type_id' => $roomType->id]);

    $checker = app(AvailabilityChecker::class);

    expect($checker->isAvailable($roomType, Carbon::parse('2026-04-01'), Carbon::parse('2026-04-03')))->toBeTrue();
});

test('a cancelled reservation does not count against availability', function (): void {
    $branch = Branch::factory()->create();
    $roomType = RoomType::factory()->create(['branch_id' => $branch->id]);
    Room::factory(1)->create(['branch_id' => $branch->id, 'room_type_id' => $roomType->id]);

    $reservation = Reservation::factory()->cancelled()->create([
        'branch_id' => $branch->id,
        'arrival_date' => '2026-04-01',
        'departure_date' => '2026-04-03',
    ]);
    ReservationRoom::factory()->create(['reservation_id' => $reservation->id, 'room_type_id' => $roomType->id]);

    $checker = app(AvailabilityChecker::class);

    expect($checker->isAvailable($roomType, Carbon::parse('2026-04-01'), Carbon::parse('2026-04-03')))->toBeTrue();
});

test('a room marked out of order does not count toward inventory', function (): void {
    $branch = Branch::factory()->create();
    $roomType = RoomType::factory()->create(['branch_id' => $branch->id]);
    Room::factory()->create(['branch_id' => $branch->id, 'room_type_id' => $roomType->id, 'status' => 'out_of_order']);

    $checker = app(AvailabilityChecker::class);

    expect($checker->isAvailable($roomType, Carbon::parse('2026-04-01'), Carbon::parse('2026-04-03')))->toBeFalse();
});
