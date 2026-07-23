<?php

declare(strict_types=1);

use App\Domain\FrontDesk\Enums\ChargeType;
use App\Domain\Reservation\Enums\ReservationStatus;
use App\Domain\Room\Enums\RoomStatus;
use App\Models\Branch;
use App\Models\Folio;
use App\Models\Guest;
use App\Models\Reservation;
use App\Models\Room;
use App\Models\RoomType;
use App\Models\Tenant;
use App\Models\User;
use Spatie\Permission\Models\Role;

test('a branch belongs to a tenant and can list its rooms', function (): void {
    $branch = Branch::factory()->create();
    $roomType = RoomType::factory()->create(['branch_id' => $branch->id]);
    Room::factory(3)->create(['branch_id' => $branch->id, 'room_type_id' => $roomType->id]);

    expect($branch->tenant)->toBeInstanceOf(Tenant::class)
        ->and($branch->rooms)->toHaveCount(3);
});

test('a bookable room reports bookable only when vacant and active', function (): void {
    $room = Room::factory()->create(['status' => RoomStatus::VacantClean, 'is_active' => true]);
    expect($room->isBookable())->toBeTrue();

    $room->update(['status' => RoomStatus::Occupied]);
    expect($room->fresh()->isBookable())->toBeFalse();
});

test('a reservation, its room assignment, and its folio resolve through their relationships', function (): void {
    $branch = Branch::factory()->create();
    $guest = Guest::factory()->create(['tenant_id' => $branch->tenant_id]);
    $roomType = RoomType::factory()->create(['branch_id' => $branch->id]);
    $room = Room::factory()->create(['branch_id' => $branch->id, 'room_type_id' => $roomType->id]);

    $reservation = Reservation::factory()->checkedIn()->create([
        'branch_id' => $branch->id,
        'guest_id' => $guest->id,
    ]);

    $reservation->rooms()->create([
        'room_type_id' => $roomType->id,
        'room_id' => $room->id,
        'rate_cents' => $roomType->base_rate_cents,
    ]);

    $folio = Folio::factory()->create([
        'branch_id' => $branch->id,
        'reservation_id' => $reservation->id,
        'guest_id' => $guest->id,
    ]);

    $folio->charges()->create([
        'charge_type' => ChargeType::Room,
        'amount_cents' => $roomType->base_rate_cents,
        'charge_date' => now()->toDateString(),
    ]);

    expect($reservation->status)->toBe(ReservationStatus::CheckedIn)
        ->and($reservation->rooms)->toHaveCount(1)
        ->and($reservation->rooms->first()->room->id)->toBe($room->id)
        ->and($reservation->folio->id)->toBe($folio->id)
        ->and($folio->charges)->toHaveCount(1)
        ->and($folio->charges->first()->amount_cents)->toBe($roomType->base_rate_cents);
});

test('branch access is granted to assigned staff and denied to unassigned staff', function (): void {
    $branchA = Branch::factory()->create();
    $branchB = Branch::factory()->create(['tenant_id' => $branchA->tenant_id]);

    $role = Role::firstOrCreate(['name' => 'Receptionist', 'guard_name' => 'web']);

    $receptionist = User::factory()->create(['tenant_id' => $branchA->tenant_id]);
    $branchA->staff()->attach($receptionist->id, ['role_id' => $role->id, 'is_primary' => true]);

    expect($receptionist->canAccessBranch($branchA->id))->toBeTrue()
        ->and($receptionist->canAccessBranch($branchB->id))->toBeFalse();
});

test('a group-level role can access every branch in its tenant without explicit assignment', function (): void {
    Role::firstOrCreate(['name' => 'General Manager', 'guard_name' => 'web']);

    $branch = Branch::factory()->create();
    $gm = User::factory()->create(['tenant_id' => $branch->tenant_id]);
    $gm->assignRole('General Manager');

    expect($gm->canAccessBranch($branch->id))->toBeTrue();
});
