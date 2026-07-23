<?php

declare(strict_types=1);

use App\Domain\Reservation\Enums\ReservationStatus;
use App\Livewire\Reservations\ReservationShow;
use App\Models\Branch;
use App\Models\Reservation;
use App\Models\User;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

beforeEach(function (): void {
    Permission::firstOrCreate(['name' => 'reservations.manage', 'guard_name' => 'web']);
    $role = Role::firstOrCreate(['name' => 'Receptionist', 'guard_name' => 'web']);
    $role->givePermissionTo('reservations.manage');

    $this->branch = Branch::factory()->create();
    $this->staff = User::factory()->create(['tenant_id' => $this->branch->tenant_id, 'current_branch_id' => $this->branch->id]);
    $this->staff->assignRole($role);
    $this->branch->staff()->attach($this->staff->id, ['role_id' => $role->id, 'is_primary' => true]);
});

test('staff can cancel a confirmed reservation from the show page', function (): void {
    $reservation = Reservation::factory()->create(['branch_id' => $this->branch->id, 'status' => ReservationStatus::Confirmed]);

    Livewire::actingAs($this->staff)
        ->test(ReservationShow::class, ['reservation' => $reservation])
        ->set('showCancelForm', true)
        ->set('cancelReason', 'Guest request')
        ->call('cancel')
        ->assertHasNoErrors();

    expect($reservation->fresh()->status)->toBe(ReservationStatus::Cancelled);
});

test('a guest from another branch cannot be viewed', function (): void {
    $otherBranch = Branch::factory()->create();
    $reservation = Reservation::factory()->create(['branch_id' => $otherBranch->id]);

    Livewire::actingAs($this->staff)
        ->test(ReservationShow::class, ['reservation' => $reservation])
        ->assertForbidden();
});
