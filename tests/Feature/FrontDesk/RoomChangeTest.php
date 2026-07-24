<?php

declare(strict_types=1);

use App\Domain\FrontDesk\Actions\ChangeReservationRoomAction;
use App\Domain\FrontDesk\Actions\CheckInGuestAction;
use App\Domain\Reservation\Enums\ReservationStatus;
use App\Domain\Room\Enums\RoomStatus;
use App\Livewire\FrontDesk\Dashboard;
use App\Models\Branch;
use App\Models\Reservation;
use App\Models\ReservationRoom;
use App\Models\Room;
use App\Models\RoomType;
use App\Models\User;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

/**
 * @return array{0: Reservation, 1: Room, 2: RoomType} a confirmed, not-yet-checked-in
 *                                                     reservation with its booked room and room type
 */
function makeUpcomingStay(Branch $branch, int $nights = 2, int $nightlyRateCents = 10000): array
{
    $roomType = RoomType::factory()->create(['branch_id' => $branch->id, 'base_rate_cents' => $nightlyRateCents]);
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
        'rate_cents' => $nightlyRateCents,
    ]);

    return [$reservation, $room, $roomType];
}

test('changing rooms before check-in rebooks the type and rate but leaves rooms untouched', function (): void {
    $branch = Branch::factory()->create();
    [$reservation] = makeUpcomingStay($branch, nights: 2, nightlyRateCents: 10000);
    $upgradedType = RoomType::factory()->create(['branch_id' => $branch->id, 'base_rate_cents' => 20000]);
    $upgradedRoom = Room::factory()->create(['branch_id' => $branch->id, 'room_type_id' => $upgradedType->id, 'status' => RoomStatus::VacantClean]);
    $staff = User::factory()->create();

    $reservationRoom = app(ChangeReservationRoomAction::class)->handle($reservation, $upgradedRoom, $staff);

    expect($reservationRoom->room_id)->toBe($upgradedRoom->id)
        ->and($reservationRoom->room_type_id)->toBe($upgradedType->id)
        ->and($reservationRoom->rate_cents)->toBe(20000)
        ->and($upgradedRoom->fresh()->status)->toBe(RoomStatus::VacantClean)
        ->and($reservation->fresh()->status)->toBe(ReservationStatus::Confirmed);
});

test('changing to a same-type room after check-in vacates the old room and occupies the new one', function (): void {
    $branch = Branch::factory()->create();
    [$reservation, $room, $roomType] = makeUpcomingStay($branch, nights: 2);
    $otherRoom = Room::factory()->create(['branch_id' => $branch->id, 'room_type_id' => $roomType->id, 'status' => RoomStatus::VacantClean]);
    $staff = User::factory()->create();

    $folio = app(CheckInGuestAction::class)->handle($reservation, $room, $staff);

    app(ChangeReservationRoomAction::class)->handle($reservation->fresh(), $otherRoom, $staff, 'Guest requested a quieter room');

    expect($room->fresh()->status)->toBe(RoomStatus::VacantDirty)
        ->and($otherRoom->fresh()->status)->toBe(RoomStatus::Occupied)
        ->and($reservation->fresh()->rooms->first()->room_id)->toBe($otherRoom->id)
        ->and($folio->fresh()->charges)->toHaveCount(2)
        ->and($folio->fresh()->balance_cents)->toBe(20000);
});

test('upgrading to a different room type after check-in reprices the remaining nights without touching past charges', function (): void {
    $branch = Branch::factory()->create();
    [$reservation, $room] = makeUpcomingStay($branch, nights: 3, nightlyRateCents: 10000);
    $suiteType = RoomType::factory()->create(['branch_id' => $branch->id, 'base_rate_cents' => 15000]);
    $suite = Room::factory()->create(['branch_id' => $branch->id, 'room_type_id' => $suiteType->id, 'status' => RoomStatus::VacantClean]);
    $staff = User::factory()->create();

    $folio = app(CheckInGuestAction::class)->handle($reservation, $room, $staff);
    expect($folio->charges)->toHaveCount(3)
        ->and($folio->balance_cents)->toBe(30000);

    app(ChangeReservationRoomAction::class)->handle($reservation->fresh(), $suite, $staff, 'Complimentary upgrade');

    $folio->refresh();

    // 3 original nightly charges + 1 reversal of all 3 (still-future, since
    // "today" is the arrival day in this test) + 3 nights reposted at the
    // new rate.
    expect($folio->charges)->toHaveCount(7)
        ->and($folio->charges()->where('amount_cents', -30000)->count())->toBe(1)
        ->and($folio->charges()->where('amount_cents', 15000)->count())->toBe(3)
        ->and($folio->balance_cents)->toBe(45000);
});

test('a room that is not currently bookable cannot be moved into', function (): void {
    $branch = Branch::factory()->create();
    [$reservation, $room, $roomType] = makeUpcomingStay($branch);
    $occupiedRoom = Room::factory()->create(['branch_id' => $branch->id, 'room_type_id' => $roomType->id, 'status' => RoomStatus::Occupied]);
    $staff = User::factory()->create();

    app(CheckInGuestAction::class)->handle($reservation, $room, $staff);

    app(ChangeReservationRoomAction::class)->handle($reservation->fresh(), $occupiedRoom, $staff);
})->throws(ValidationException::class);

test('moving a reservation into the room it already occupies is rejected', function (): void {
    $branch = Branch::factory()->create();
    [$reservation, $room] = makeUpcomingStay($branch);
    $staff = User::factory()->create();

    app(CheckInGuestAction::class)->handle($reservation, $room, $staff);

    app(ChangeReservationRoomAction::class)->handle($reservation->fresh(), $room, $staff);
})->throws(ValidationException::class);

test('a reservation with no room booking at all cannot change rooms', function (): void {
    $branch = Branch::factory()->create();
    $roomType = RoomType::factory()->create(['branch_id' => $branch->id]);
    $room = Room::factory()->create(['branch_id' => $branch->id, 'room_type_id' => $roomType->id, 'status' => RoomStatus::VacantClean]);
    $reservation = Reservation::factory()->create(['branch_id' => $branch->id, 'status' => ReservationStatus::Confirmed]);
    $staff = User::factory()->create();

    app(ChangeReservationRoomAction::class)->handle($reservation, $room, $staff);
})->throws(ValidationException::class);

test('the front desk dashboard lets staff pick a different room type at check-in as a one-step upgrade', function (): void {
    Permission::firstOrCreate(['name' => 'reservations.manage', 'guard_name' => 'web']);
    $role = Role::firstOrCreate(['name' => 'Front Desk Boundary Role', 'guard_name' => 'web']);
    $role->givePermissionTo('reservations.manage');

    $branch = Branch::factory()->create();
    [$reservation, , $bookedType] = makeUpcomingStay($branch, nights: 1, nightlyRateCents: 10000);
    $upgradedType = RoomType::factory()->create(['branch_id' => $branch->id, 'base_rate_cents' => 18000]);
    $upgradedRoom = Room::factory()->create(['branch_id' => $branch->id, 'room_type_id' => $upgradedType->id, 'status' => RoomStatus::VacantClean]);

    $staff = User::factory()->create(['tenant_id' => $branch->tenant_id, 'current_branch_id' => $branch->id]);
    $staff->assignRole($role);
    $branch->staff()->attach($staff->id, ['role_id' => $role->id, 'is_primary' => true]);

    Livewire::actingAs($staff)
        ->test(Dashboard::class)
        ->call('startCheckIn', $reservation->id)
        ->set('selectedRoomId', $upgradedRoom->id)
        ->call('completeCheckIn')
        ->assertOk();

    $reservationRoom = $reservation->fresh()->rooms->first();

    expect($reservation->fresh()->status)->toBe(ReservationStatus::CheckedIn)
        ->and($reservationRoom->room_type_id)->toBe($upgradedType->id)
        ->and($reservationRoom->room_id)->toBe($upgradedRoom->id)
        ->and($upgradedRoom->fresh()->status)->toBe(RoomStatus::Occupied)
        ->and($bookedType->id)->not->toBe($upgradedType->id);
});

test('the front desk dashboard lets staff move an in-house guest to a new room', function (): void {
    Permission::firstOrCreate(['name' => 'reservations.manage', 'guard_name' => 'web']);
    $role = Role::firstOrCreate(['name' => 'Front Desk Boundary Role 2', 'guard_name' => 'web']);
    $role->givePermissionTo('reservations.manage');

    $branch = Branch::factory()->create();
    [$reservation, $room, $roomType] = makeUpcomingStay($branch);
    $newRoom = Room::factory()->create(['branch_id' => $branch->id, 'room_type_id' => $roomType->id, 'status' => RoomStatus::VacantClean]);

    $staff = User::factory()->create(['tenant_id' => $branch->tenant_id, 'current_branch_id' => $branch->id]);
    $staff->assignRole($role);
    $branch->staff()->attach($staff->id, ['role_id' => $role->id, 'is_primary' => true]);

    app(CheckInGuestAction::class)->handle($reservation, $room, $staff);

    Livewire::actingAs($staff)
        ->test(Dashboard::class)
        ->set('tab', 'in_house')
        ->call('startRoomChange', $reservation->id)
        ->set('selectedNewRoomId', $newRoom->id)
        ->set('roomChangeReason', 'Guest requested a room away from the elevator')
        ->call('completeRoomChange')
        ->assertOk();

    expect($reservation->fresh()->rooms->first()->room_id)->toBe($newRoom->id)
        ->and($room->fresh()->status)->toBe(RoomStatus::VacantDirty)
        ->and($newRoom->fresh()->status)->toBe(RoomStatus::Occupied);
});

test('a tampered room-change selection outside the acting branch is rejected', function (): void {
    Permission::firstOrCreate(['name' => 'reservations.manage', 'guard_name' => 'web']);
    $role = Role::firstOrCreate(['name' => 'Front Desk Boundary Role 3', 'guard_name' => 'web']);
    $role->givePermissionTo('reservations.manage');

    $branch = Branch::factory()->create();
    $otherBranch = Branch::factory()->create(['tenant_id' => $branch->tenant_id]);
    [$reservation, $room] = makeUpcomingStay($branch);
    $otherBranchType = RoomType::factory()->create(['branch_id' => $otherBranch->id]);
    $otherBranchRoom = Room::factory()->create(['branch_id' => $otherBranch->id, 'room_type_id' => $otherBranchType->id, 'status' => RoomStatus::VacantClean]);

    $staff = User::factory()->create(['tenant_id' => $branch->tenant_id, 'current_branch_id' => $branch->id]);
    $staff->assignRole($role);
    $branch->staff()->attach($staff->id, ['role_id' => $role->id, 'is_primary' => true]);

    app(CheckInGuestAction::class)->handle($reservation, $room, $staff);

    Livewire::actingAs($staff)
        ->test(Dashboard::class)
        ->set('tab', 'in_house')
        ->call('startRoomChange', $reservation->id)
        ->set('selectedNewRoomId', $otherBranchRoom->id)
        ->call('completeRoomChange')
        ->assertForbidden();

    expect($reservation->fresh()->rooms->first()->room_id)->toBe($room->id);
});
