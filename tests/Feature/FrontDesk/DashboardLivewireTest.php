<?php

declare(strict_types=1);

use App\Domain\FrontDesk\Enums\ChargeType;
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
    Permission::firstOrCreate(['name' => 'folios.manage', 'guard_name' => 'web']);
    Permission::firstOrCreate(['name' => 'folios.force_checkout', 'guard_name' => 'web']);
    $role = Role::firstOrCreate(['name' => 'Receptionist', 'guard_name' => 'web']);
    $role->givePermissionTo(['reservations.manage', 'folios.manage']);

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

test('checking in early with a fee amount posts an early check-in charge', function (): void {
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
        ->call('startCheckIn', $reservation->id)
        ->set('selectedRoomId', $this->room->id)
        ->set('earlyCheckInFee', '15.00')
        ->call('completeCheckIn')
        ->assertHasNoErrors();

    $feeCharge = $reservation->fresh()->folio->charges->firstWhere('charge_type', ChargeType::EarlyCheckin);
    expect($feeCharge)->not->toBeNull()
        ->and($feeCharge->amount_cents)->toBe(1500);
});

test('recording a payment on the departures tab reduces the folio balance', function (): void {
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
    $folio = $reservation->folio()->create([
        'branch_id' => $this->branch->id,
        'guest_id' => $reservation->guest_id,
        'balance_cents' => 5000,
    ]);
    $folio->charges()->create([
        'charge_type' => ChargeType::Room,
        'description' => 'Room charge',
        'amount_cents' => 5000,
        'charge_date' => now()->toDateString(),
        'posted_by_user_id' => $this->staff->id,
    ]);

    Livewire::actingAs($this->staff)
        ->test(Dashboard::class)
        ->set('tab', 'departures')
        ->call('startPayment', $reservation->id)
        ->set('paymentMethod', 'cash')
        ->set('paymentAmount', '50.00')
        ->call('recordPayment')
        ->assertHasNoErrors();

    expect($folio->fresh()->balance_cents)->toBe(0);

    Livewire::actingAs($this->staff)
        ->test(Dashboard::class)
        ->set('tab', 'departures')
        ->call('checkOut', $reservation->id)
        ->assertSet('checkoutError', null);

    expect($reservation->fresh()->status)->toBe(ReservationStatus::CheckedOut);
});

test('adding a late checkout fee increases the folio balance', function (): void {
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
    $folio = $reservation->folio()->create([
        'branch_id' => $this->branch->id,
        'guest_id' => $reservation->guest_id,
        'balance_cents' => 0,
    ]);

    Livewire::actingAs($this->staff)
        ->test(Dashboard::class)
        ->set('tab', 'departures')
        ->call('startLateFee', $reservation->id)
        ->set('lateFeeAmount', '25.00')
        ->call('confirmLateFee')
        ->assertHasNoErrors();

    $charge = $folio->fresh()->charges->first();
    expect($charge->charge_type)->toBe(ChargeType::LateCheckout)
        ->and($charge->amount_cents)->toBe(2500)
        ->and($folio->fresh()->balance_cents)->toBe(2500);
});

test('a user without folios.force_checkout cannot start a force checkout', function (): void {
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
        ->call('startForceCheckout', $reservation->id)
        ->assertForbidden();
});

test('a user with folios.force_checkout can override an outstanding balance, but a reason is required', function (): void {
    $managerRole = Role::firstOrCreate(['name' => 'Branch Manager', 'guard_name' => 'web']);
    $managerRole->givePermissionTo(['reservations.manage', 'folios.force_checkout']);
    $manager = User::factory()->create(['tenant_id' => $this->branch->tenant_id, 'current_branch_id' => $this->branch->id]);
    $manager->assignRole($managerRole);
    $this->branch->staff()->attach($manager->id, ['role_id' => $managerRole->id, 'is_primary' => true]);

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

    Livewire::actingAs($manager)
        ->test(Dashboard::class)
        ->set('tab', 'departures')
        ->call('startForceCheckout', $reservation->id)
        ->call('confirmForceCheckout')
        ->assertHasErrors(['forceCheckoutReason']);

    expect($reservation->fresh()->status)->toBe(ReservationStatus::CheckedIn);

    Livewire::actingAs($manager)
        ->test(Dashboard::class)
        ->set('tab', 'departures')
        ->call('startForceCheckout', $reservation->id)
        ->set('forceCheckoutReason', 'Guest disputes charge, will settle later.')
        ->call('confirmForceCheckout')
        ->assertHasNoErrors();

    expect($reservation->fresh()->status)->toBe(ReservationStatus::CheckedOut);
});
