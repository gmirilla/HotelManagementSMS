<?php

declare(strict_types=1);

/**
 * Boundary tests for the User Management feature (UserPolicy, UserManager),
 * proactively covering the bug classes found and fixed during the
 * Deliverable 9 security audit — see AuthorizationBoundaryTest.php — rather
 * than waiting for a second audit pass to find them here too:
 *
 * 1. Tenant isolation: a users.manage holder must never view/edit/deactivate/
 *    reactivate a user from a different tenant, even with the permission.
 * 2. IDOR: the branch checkbox list is client-mutable, so UserManager::save()
 *    must re-validate every selected branch belongs to the actor's tenant.
 * 3. Privilege escalation: only a Super Administrator may assign the Super
 *    Administrator or Hotel Owner roles to another user.
 * 4. A user may never deactivate their own account through this screen.
 */

use App\Livewire\Admin\UserManager;
use App\Models\Branch;
use App\Models\Tenant;
use App\Models\User;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

beforeEach(function (): void {
    Permission::firstOrCreate(['name' => 'users.manage', 'guard_name' => 'web']);
    Permission::firstOrCreate(['name' => 'users.view', 'guard_name' => 'web']);

    $this->generalManagerRole = Role::firstOrCreate(['name' => 'General Manager', 'guard_name' => 'web']);
    $this->generalManagerRole->givePermissionTo('users.manage');

    $this->superAdminRole = Role::firstOrCreate(['name' => 'Super Administrator', 'guard_name' => 'web']);
    $this->superAdminRole->givePermissionTo(['users.manage', 'users.view']);

    Role::firstOrCreate(['name' => 'Hotel Owner', 'guard_name' => 'web']);
    Role::firstOrCreate(['name' => 'Housekeeping Staff', 'guard_name' => 'web']);
});

test('a users.manage holder cannot view, update, deactivate, or restore a user from a different tenant', function (): void {
    $ownTenant = Tenant::factory()->create();
    $otherTenant = Tenant::factory()->create();

    $gm = User::factory()->create(['tenant_id' => $ownTenant->id]);
    $gm->assignRole($this->generalManagerRole);

    $otherTenantUser = User::factory()->create(['tenant_id' => $otherTenant->id]);

    expect($gm->can('view', $otherTenantUser))->toBeFalse()
        ->and($gm->can('update', $otherTenantUser))->toBeFalse()
        ->and($gm->can('delete', $otherTenantUser))->toBeFalse()
        ->and($gm->can('restore', $otherTenantUser))->toBeFalse();

    Livewire::actingAs($gm)
        ->test(UserManager::class)
        ->call('edit', $otherTenantUser->id)
        ->assertForbidden();

    Livewire::actingAs($gm)
        ->test(UserManager::class)
        ->call('deactivate', $otherTenantUser->id)
        ->assertForbidden();

    expect($otherTenantUser->fresh()->trashed())->toBeFalse();
});

test('a user cannot select a branch from outside their tenant when saving', function (): void {
    $ownBranch = Branch::factory()->create();
    $otherTenantBranch = Branch::factory()->create();

    $gm = User::factory()->create(['tenant_id' => $ownBranch->tenant_id]);
    $gm->assignRole($this->generalManagerRole);

    Livewire::actingAs($gm)
        ->test(UserManager::class)
        ->set('name', 'Tampered Staffer')
        ->set('email', 'tampered@aurorahotels.test')
        ->set('password', 'Correct-Horse-42!')
        ->set('password_confirmation', 'Correct-Horse-42!')
        ->set('roleName', 'Housekeeping Staff')
        ->set('selectedBranchIds', [$otherTenantBranch->id])
        ->call('save')
        ->assertForbidden();

    expect(User::where('email', 'tampered@aurorahotels.test')->exists())->toBeFalse();
});

test('only a Super Administrator can assign the Super Administrator or Hotel Owner role', function (): void {
    $ownBranch = Branch::factory()->create();

    $gm = User::factory()->create(['tenant_id' => $ownBranch->tenant_id]);
    $gm->assignRole($this->generalManagerRole);

    expect($gm->can('assignRole', [User::class, 'Super Administrator']))->toBeFalse()
        ->and($gm->can('assignRole', [User::class, 'Hotel Owner']))->toBeFalse();

    Livewire::actingAs($gm)
        ->test(UserManager::class)
        ->set('name', 'Sneaky Owner')
        ->set('email', 'sneaky@aurorahotels.test')
        ->set('password', 'Correct-Horse-42!')
        ->set('password_confirmation', 'Correct-Horse-42!')
        ->set('roleName', 'Hotel Owner')
        ->call('save')
        ->assertForbidden();

    expect(User::where('email', 'sneaky@aurorahotels.test')->exists())->toBeFalse();

    $superAdmin = User::factory()->create(['tenant_id' => $ownBranch->tenant_id]);
    $superAdmin->assignRole($this->superAdminRole);

    expect($superAdmin->can('assignRole', [User::class, 'Hotel Owner']))->toBeTrue();
});

test('a user cannot deactivate their own account', function (): void {
    $gm = User::factory()->create();
    $gm->assignRole($this->generalManagerRole);

    expect($gm->can('delete', $gm))->toBeFalse();

    Livewire::actingAs($gm)
        ->test(UserManager::class)
        ->call('deactivate', $gm->id)
        ->assertForbidden();

    expect($gm->fresh()->trashed())->toBeFalse();
});
