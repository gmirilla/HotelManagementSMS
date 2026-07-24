<?php

declare(strict_types=1);

/**
 * Full-page Livewire render + happy-path tests for the User Management
 * screen. Boundary/authorization failure cases live in
 * tests/Feature/Security/UserManagementBoundaryTest.php.
 */

use App\Livewire\Admin\UserManager;
use App\Models\Branch;
use App\Models\User;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

beforeEach(function (): void {
    Permission::firstOrCreate(['name' => 'users.manage', 'guard_name' => 'web']);

    $this->generalManagerRole = Role::firstOrCreate(['name' => 'General Manager', 'guard_name' => 'web']);
    $this->generalManagerRole->givePermissionTo('users.manage');

    Role::firstOrCreate(['name' => 'Super Administrator', 'guard_name' => 'web']);
    Role::firstOrCreate(['name' => 'Hotel Owner', 'guard_name' => 'web']);
    $this->staffRole = Role::firstOrCreate(['name' => 'Housekeeping Staff', 'guard_name' => 'web']);

    $this->branch = Branch::factory()->create();
    $this->gm = User::factory()->create(['tenant_id' => $this->branch->tenant_id]);
    $this->gm->assignRole($this->generalManagerRole);
});

test('user manager renders with just the acting user', function (): void {
    Livewire::actingAs($this->gm)->test(UserManager::class)->assertOk();
});

test('user manager renders with other staff present and searches by name', function (): void {
    User::factory()->create(['tenant_id' => $this->branch->tenant_id, 'name' => 'Zendaya Housekeeper']);

    $component = Livewire::actingAs($this->gm)->test(UserManager::class)->assertOk();

    $component->set('search', 'Zendaya')->assertOk()->assertSee('Zendaya Housekeeper');
});

test('a users.manage holder can create a staff user with a branch assignment', function (): void {
    Livewire::actingAs($this->gm)
        ->test(UserManager::class)
        ->call('create')
        ->set('name', 'Nadia Housekeeper')
        ->set('email', 'nadia@aurorahotels.test')
        ->set('password', 'Correct-Horse-42!')
        ->set('password_confirmation', 'Correct-Horse-42!')
        ->set('roleName', 'Housekeeping Staff')
        ->set('selectedBranchIds', [$this->branch->id])
        ->call('save')
        ->assertOk()
        ->assertSet('showForm', false);

    $created = User::where('email', 'nadia@aurorahotels.test')->firstOrFail();

    expect($created->hasRole('Housekeeping Staff'))->toBeTrue()
        ->and($created->branches()->pluck('branches.id')->all())->toBe([$this->branch->id]);
});

test('saving without a branch for a branch-scoped role fails validation', function (): void {
    Livewire::actingAs($this->gm)
        ->test(UserManager::class)
        ->call('create')
        ->set('name', 'No Branch Staffer')
        ->set('email', 'nobranch@aurorahotels.test')
        ->set('password', 'Correct-Horse-42!')
        ->set('password_confirmation', 'Correct-Horse-42!')
        ->set('roleName', 'Housekeeping Staff')
        ->call('save')
        ->assertHasErrors('selectedBranchIds');

    expect(User::where('email', 'nobranch@aurorahotels.test')->exists())->toBeFalse();
});

test('a users.manage holder can edit and then deactivate and reactivate a staff user', function (): void {
    $staff = User::factory()->create(['tenant_id' => $this->branch->tenant_id]);
    $staff->assignRole($this->staffRole);
    $this->branch->staff()->attach($staff->id, ['role_id' => $this->staffRole->id, 'is_primary' => true]);

    Livewire::actingAs($this->gm)
        ->test(UserManager::class)
        ->call('edit', $staff->id)
        ->assertSet('name', $staff->name)
        ->assertSet('roleName', 'Housekeeping Staff')
        ->set('name', 'Renamed Staffer')
        ->call('save')
        ->assertOk();

    expect($staff->fresh()->name)->toBe('Renamed Staffer');

    Livewire::actingAs($this->gm)
        ->test(UserManager::class)
        ->call('deactivate', $staff->id)
        ->assertOk();

    expect($staff->fresh()->trashed())->toBeTrue();

    Livewire::actingAs($this->gm)
        ->test(UserManager::class)
        ->call('reactivate', $staff->id)
        ->assertOk();

    expect($staff->fresh()->trashed())->toBeFalse();
});
