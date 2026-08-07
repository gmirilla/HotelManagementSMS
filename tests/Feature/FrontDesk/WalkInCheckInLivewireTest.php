<?php

declare(strict_types=1);

use App\Domain\Reservation\Enums\ReservationStatus;
use App\Domain\Room\Enums\RoomStatus;
use App\Livewire\FrontDesk\WalkInCheckIn;
use App\Models\Branch;
use App\Models\Guest;
use App\Models\GuestDocument;
use App\Models\Reservation;
use App\Models\Room;
use App\Models\RoomType;
use App\Models\User;
use Livewire\Features\SupportTesting\Testable;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

beforeEach(function (): void {
    Permission::firstOrCreate(['name' => 'reservations.manage', 'guard_name' => 'web']);
    Permission::firstOrCreate(['name' => 'guests.manage', 'guard_name' => 'web']);
    $role = Role::firstOrCreate(['name' => 'Receptionist', 'guard_name' => 'web']);
    $role->givePermissionTo(['reservations.manage', 'guests.manage']);

    $this->branch = Branch::factory()->create();
    seedChartOfAccounts($this->branch);
    $this->roomType = RoomType::factory()->create(['branch_id' => $this->branch->id, 'base_rate_cents' => 10000]);
    $this->room = Room::factory()->create(['branch_id' => $this->branch->id, 'room_type_id' => $this->roomType->id]);

    $this->staff = User::factory()->create(['tenant_id' => $this->branch->tenant_id, 'current_branch_id' => $this->branch->id]);
    $this->staff->assignRole($role);
    $this->branch->staff()->attach($this->staff->id, ['role_id' => $role->id, 'is_primary' => true]);
});

function progressToGuestStep(): Testable
{
    return Livewire::actingAs(test()->staff)
        ->test(WalkInCheckIn::class)
        ->call('proceedToRoomType')
        ->assertSet('step', 2)
        ->call('selectRoomType', test()->roomType->id)
        ->assertSet('step', 3)
        ->call('selectRoom', test()->room->id)
        ->assertSet('step', 4);
}

test('walk-in check-in with an existing guest opens a folio and redirects there', function (): void {
    $guest = Guest::factory()->create(['tenant_id' => $this->branch->tenant_id]);

    progressToGuestStep()
        ->call('selectGuest', $guest->id)
        ->call('confirmCheckIn')
        ->assertHasNoErrors();

    $reservation = Reservation::where('guest_id', $guest->id)->firstOrFail();
    expect($reservation->status)->toBe(ReservationStatus::CheckedIn)
        ->and($this->room->fresh()->status)->toBe(RoomStatus::Occupied)
        ->and($reservation->folio)->not->toBeNull();
});

test('walk-in check-in with a new guest creates the guest, an ID document, and checks them in', function (): void {
    progressToGuestStep()
        ->set('creatingNewGuest', true)
        ->set('newGuestFirstName', 'Jordan')
        ->set('newGuestLastName', 'Rivera')
        ->set('newGuestEmail', 'jordan.rivera@example.com')
        ->set('documentType', 'passport')
        ->set('documentNumber', 'X1234567')
        ->set('documentIssuingCountry', 'NG')
        ->call('confirmCheckIn')
        ->assertHasNoErrors();

    $guest = Guest::where('email', 'jordan.rivera@example.com')->firstOrFail();
    $document = GuestDocument::where('guest_id', $guest->id)->firstOrFail();

    expect($document->document_type->value)->toBe('passport')
        ->and($document->document_number)->toBe('X1234567');

    $reservation = Reservation::where('guest_id', $guest->id)->firstOrFail();
    expect($reservation->status)->toBe(ReservationStatus::CheckedIn);
});

test('a blacklisted guest shows a warning but check-in still succeeds', function (): void {
    $guest = Guest::factory()->blacklisted()->create(['tenant_id' => $this->branch->tenant_id]);

    progressToGuestStep()
        ->call('selectGuest', $guest->id)
        ->assertSee('blacklisted')
        ->call('confirmCheckIn')
        ->assertHasNoErrors();

    expect(Reservation::where('guest_id', $guest->id)->firstOrFail()->status)->toBe(ReservationStatus::CheckedIn);
});

test('room type becoming unavailable before submit surfaces an error and returns to the room type step', function (): void {
    $guest = Guest::factory()->create(['tenant_id' => $this->branch->tenant_id]);

    $component = progressToGuestStep()->call('selectGuest', $guest->id);

    // The only room of this type is pulled from inventory between step 2 and submission.
    $this->room->update(['is_active' => false]);

    $component->call('confirmCheckIn')
        ->assertSet('step', 2)
        ->assertSet('submitError', fn (?string $error) => $error !== null);

    expect(Reservation::where('guest_id', $guest->id)->exists())->toBeFalse();
});

test('a room taken by another staff member surfaces an error and leaves the reservation confirmed', function (): void {
    $guest = Guest::factory()->create(['tenant_id' => $this->branch->tenant_id]);

    $component = progressToGuestStep()->call('selectGuest', $guest->id);

    // Simulates another receptionist checking a different guest into this
    // exact physical room a moment before this submission lands.
    $this->room->update(['status' => RoomStatus::Occupied]);

    $component->call('confirmCheckIn')
        ->assertSet('submitError', fn (?string $error) => $error !== null && str_contains($error, 'remains confirmed'));

    $reservation = Reservation::where('guest_id', $guest->id)->firstOrFail();
    expect($reservation->status)->toBe(ReservationStatus::Confirmed)
        ->and($reservation->folio)->toBeNull();
});

test('a user without reservations.create permission cannot access the walk-in check-in page', function (): void {
    $otherStaff = User::factory()->create(['tenant_id' => $this->branch->tenant_id, 'current_branch_id' => $this->branch->id]);

    Livewire::actingAs($otherStaff)->test(WalkInCheckIn::class)->assertForbidden();
});
