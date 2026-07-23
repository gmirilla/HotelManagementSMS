<?php

declare(strict_types=1);

use App\Livewire\Reservations\BookingWizard;
use App\Models\Branch;
use App\Models\Guest;
use App\Models\Reservation;
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
    $this->roomType = RoomType::factory()->create(['branch_id' => $this->branch->id, 'base_rate_cents' => 15000]);
    Room::factory()->create(['branch_id' => $this->branch->id, 'room_type_id' => $this->roomType->id]);

    $this->staff = User::factory()->create(['tenant_id' => $this->branch->tenant_id, 'current_branch_id' => $this->branch->id]);
    $this->staff->assignRole($role);
    $this->branch->staff()->attach($this->staff->id, ['role_id' => $role->id, 'is_primary' => true]);
});

test('the wizard walks through dates, room selection, and an existing guest to create a reservation', function (): void {
    $guest = Guest::factory()->create(['tenant_id' => $this->branch->tenant_id]);

    Livewire::actingAs($this->staff)
        ->test(BookingWizard::class)
        ->set('arrivalDate', now()->addDay()->toDateString())
        ->set('departureDate', now()->addDays(3)->toDateString())
        ->call('searchAvailability')
        ->assertSet('step', 2)
        ->call('selectRoomType', $this->roomType->id)
        ->assertSet('step', 3)
        ->set('guestSearch', $guest->last_name)
        ->call('selectGuest', $guest->id)
        ->call('confirm');

    expect(Reservation::where('guest_id', $guest->id)->exists())->toBeTrue();
});

test('the wizard can create a brand new guest inline and book them', function (): void {
    Livewire::actingAs($this->staff)
        ->test(BookingWizard::class)
        ->set('arrivalDate', now()->addDay()->toDateString())
        ->set('departureDate', now()->addDays(2)->toDateString())
        ->call('searchAvailability')
        ->call('selectRoomType', $this->roomType->id)
        ->set('creatingNewGuest', true)
        ->set('newGuestFirstName', 'Jordan')
        ->set('newGuestLastName', 'Rivera')
        ->set('newGuestEmail', 'jordan.rivera@example.com')
        ->call('confirm');

    expect(Guest::where('email', 'jordan.rivera@example.com')->exists())->toBeTrue();
});

test('searching with an invalid date range shows a validation error', function (): void {
    Livewire::actingAs($this->staff)
        ->test(BookingWizard::class)
        ->set('arrivalDate', now()->addDays(5)->toDateString())
        ->set('departureDate', now()->addDay()->toDateString())
        ->call('searchAvailability')
        ->assertHasErrors(['departureDate'])
        ->assertSet('step', 1);
});
