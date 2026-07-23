<?php

declare(strict_types=1);

use App\Models\Branch;
use App\Models\Folio;
use App\Models\Guest;
use App\Models\Reservation;
use App\Models\Room;
use App\Models\User;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

function grantPermission(User $user, string $permission): void
{
    Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'web']);

    $role = Role::firstOrCreate(['name' => 'Test Role ' . $permission, 'guard_name' => 'web']);
    $role->givePermissionTo($permission);
    $user->assignRole($role);
}

test('a staff member assigned to a branch can view its rooms, but an unassigned staff member cannot', function (): void {
    $branchA = Branch::factory()->create();
    $branchB = Branch::factory()->create(['tenant_id' => $branchA->tenant_id]);

    $room = Room::factory()->create(['branch_id' => $branchA->id]);

    Permission::firstOrCreate(['name' => 'rooms.view', 'guard_name' => 'web']);
    $role = Role::firstOrCreate(['name' => 'Room Viewer', 'guard_name' => 'web']);
    $role->givePermissionTo('rooms.view');

    $assignedUser = User::factory()->create(['tenant_id' => $branchA->tenant_id]);
    $assignedUser->assignRole($role);
    $branchA->staff()->attach($assignedUser->id, ['role_id' => $role->id, 'is_primary' => true]);

    $unassignedUser = User::factory()->create(['tenant_id' => $branchA->tenant_id]);
    $unassignedUser->assignRole($role);
    $branchB->staff()->attach($unassignedUser->id, ['role_id' => $role->id, 'is_primary' => true]);

    expect($assignedUser->can('view', $room))->toBeTrue()
        ->and($unassignedUser->can('view', $room))->toBeFalse();
});

test('a permission alone is not enough without branch access, and vice versa', function (): void {
    $branch = Branch::factory()->create();
    $room = Room::factory()->create(['branch_id' => $branch->id]);

    $hasPermissionOnly = User::factory()->create(['tenant_id' => $branch->tenant_id]);
    grantPermission($hasPermissionOnly, 'rooms.view');
    // Deliberately not assigned to the branch.

    $hasBranchOnly = User::factory()->create(['tenant_id' => $branch->tenant_id]);
    $role = Role::firstOrCreate(['name' => 'No Permissions Role', 'guard_name' => 'web']);
    $branch->staff()->attach($hasBranchOnly->id, ['role_id' => $role->id, 'is_primary' => true]);

    expect($hasPermissionOnly->can('view', $room))->toBeFalse()
        ->and($hasBranchOnly->can('view', $room))->toBeFalse();
});

test('a guest-role user can only view their own reservation', function (): void {
    $branch = Branch::factory()->create();

    $guestProfile = Guest::factory()->create(['tenant_id' => $branch->tenant_id]);
    $portalUser = User::factory()->create(['tenant_id' => $branch->tenant_id]);
    $portalUser->assignRole(Role::firstOrCreate(['name' => 'Guest', 'guard_name' => 'web']));
    $guestProfile->update(['user_id' => $portalUser->id]);

    $ownReservation = Reservation::factory()->create(['branch_id' => $branch->id, 'guest_id' => $guestProfile->id]);

    $otherGuest = Guest::factory()->create(['tenant_id' => $branch->tenant_id]);
    $othersReservation = Reservation::factory()->create(['branch_id' => $branch->id, 'guest_id' => $otherGuest->id]);

    expect($portalUser->can('view', $ownReservation))->toBeTrue()
        ->and($portalUser->can('view', $othersReservation))->toBeFalse();
});

test('a guest-role user can only view their own folio', function (): void {
    $branch = Branch::factory()->create();

    $guestProfile = Guest::factory()->create(['tenant_id' => $branch->tenant_id]);
    $portalUser = User::factory()->create(['tenant_id' => $branch->tenant_id]);
    $portalUser->assignRole(Role::firstOrCreate(['name' => 'Guest', 'guard_name' => 'web']));
    $guestProfile->update(['user_id' => $portalUser->id]);

    $ownFolio = Folio::factory()->create(['branch_id' => $branch->id, 'guest_id' => $guestProfile->id]);

    expect($portalUser->can('view', $ownFolio))->toBeTrue();
});

test('voiding a folio requires the distinct elevated folios.void permission, not just folios.manage', function (): void {
    Permission::firstOrCreate(['name' => 'folios.void', 'guard_name' => 'web']);

    $branch = Branch::factory()->create();
    $folio = Folio::factory()->create(['branch_id' => $branch->id]);

    $cashier = User::factory()->create(['tenant_id' => $branch->tenant_id]);
    grantPermission($cashier, 'folios.manage');
    $role = Role::where('name', 'Test Role folios.manage')->first();
    $branch->staff()->attach($cashier->id, ['role_id' => $role->id, 'is_primary' => true]);

    expect($cashier->can('update', $folio))->toBeTrue()
        ->and($cashier->can('void', $folio))->toBeFalse();
});
