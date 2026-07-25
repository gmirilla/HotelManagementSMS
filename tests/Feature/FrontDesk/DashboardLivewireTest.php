<?php

declare(strict_types=1);

use App\Domain\Reservation\Enums\ReservationStatus;
use App\Livewire\FrontDesk\Dashboard;
use App\Models\Branch;
use App\Models\Reservation;
use App\Models\ReservationRoom;
use App\Models\Room;
use App\Models\RoomType;
use App\Models\User;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

beforeEach(function (): void {
    Permission::firstOrCreate(['name' => 'reservations.manage', 'guard_name' => 'web']);
    $role = Role::firstOrCreate(['name' => 'Receptionist', 'guard_name' => 'web']);
    $role->givePermissionTo('reservations.manage');

    $this->branch = Branch::factory()->create();
    seedChartOfAccounts($this->branch);
    $this->roomType = RoomType::factory()->create(['branch_id' => $this->branch->id, 'base_rate_cents' => 10000]);
    $this->room = Room::factory()->create(['branch_id' => $this->branch->id, 'room_type_id' => $this->roomType->id]);

    $this->staff = User::factory()->create(['tenant_id' => $this->branch->tenant_id, 'current_branch_id' => $this->branch->id]);
    $this->staff->assignRole($role);
    $this->branch->staff()->attach($this->staff->id, ['role_id' => $role->id, 'is_primary' => true]);
});

test('a reservation arriving today appears on the arrivals tab and can be checked in', function (): void {
    $reservation = Reservation::factory()->create([
        'branch_id' => $this->branch->id,
        'status' => ReservationStatus::Confirmed,
        'arrival_date' => now(),
        'departure_date' => now()->addDays(2),
    ]);
    ReservationRoom::factory()->create([
        'reservation_id' => $reservation->id,
        'room_type_id' => $this->roomType->id,
    ]);

    Livewire::actingAs($this->staff)
        ->test(Dashboard::class)
        ->assertSee($reservation->confirmation_code)
        ->call('startCheckIn', $reservation->id)
        ->set('selectedRoomId', $this->room->id)
        ->call('completeCheckIn')
        ->assertHasNoErrors();

    expect($reservation->fresh()->status)->toBe(ReservationStatus::CheckedIn);
});

test('checking out a guest with an outstanding balance surfaces an error instead of throwing', function (): void {
    $reservation = Reservation::factory()->checkedIn()->create([
        'branch_id' => $this->branch->id,
        'arrival_date' => now()->subDay(),
        'departure_date' => now(),
    ]);
    ReservationRoom::factory()->create([
        'reservation_id' => $reservation->id,
        'room_type_id' => $this->roomType->id,
        'room_id' => $this->room->id,
    ]);
    $reservation->folio()->create([
        'branch_id' => $this->branch->id,
        'guest_id' => $reservation->guest_id,
        'balance_cents' => 5000,
    ]);

    Livewire::actingAs($this->staff)
        ->test(Dashboard::class)
        ->set('tab', 'departures')
        ->call('checkOut', $reservation->id)
        ->assertSet('checkoutError', fn (?string $error) => $error !== null);

    expect($reservation->fresh()->status)->toBe(ReservationStatus::CheckedIn);
});
