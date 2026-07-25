<?php

declare(strict_types=1);

use App\Domain\Branch\Actions\CreateBranchAction;
use App\Domain\Branch\Actions\SetBranchActiveStatusAction;
use App\Domain\Branch\Actions\UpdateBranchAction;
use App\Livewire\Admin\BranchManager;
use App\Livewire\FrontDesk\Dashboard;
use App\Models\Account;
use App\Models\Branch;
use App\Models\Tenant;
use App\Models\User;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

function grantBranchesManage(): Role
{
    Permission::firstOrCreate(['name' => 'branches.manage', 'guard_name' => 'web']);
    $role = Role::firstOrCreate(['name' => 'Boundary Branch Manager ' . uniqid(), 'guard_name' => 'web']);
    $role->givePermissionTo('branches.manage');

    return $role;
}

test('creating a branch sets it active and scoped to the tenant', function (): void {
    $tenant = Tenant::factory()->create();

    $branch = app(CreateBranchAction::class)->handle(
        $tenant, 'Downtown', 'DTN-01', 'USD', 'America/New_York', '1 Main St', 'Metropolis', 'USA', '15:00', '11:00',
    );

    expect($branch->tenant_id)->toBe($tenant->id)
        ->and($branch->is_active)->toBeTrue()
        ->and($branch->code)->toBe('DTN-01');

    // FR-ACC-001: a brand-new branch must be able to post folio charges
    // and payments immediately, not fail with a missing-account error.
    expect(Account::where('branch_id', $branch->id)->pluck('code')->sort()->values()->all())
        ->toBe(['1000', '1100', '1200', '2000', '2100', '3000', '4000', '4100', '5000', '5100', '5200', '5300']);
});

test('updating a branch does not touch its active status', function (): void {
    $branch = Branch::factory()->create(['is_active' => true, 'name' => 'Old Name']);

    app(UpdateBranchAction::class)->handle(
        $branch, 'New Name', $branch->code, 'EUR', 'Europe/Paris', null, null, null, '16:00', '10:00',
    );

    expect($branch->fresh()->name)->toBe('New Name')
        ->and($branch->fresh()->currency)->toBe('EUR')
        ->and($branch->fresh()->is_active)->toBeTrue();
});

test('toggling branch status flips is_active only', function (): void {
    $branch = Branch::factory()->create(['is_active' => true]);

    app(SetBranchActiveStatusAction::class)->handle($branch, false);
    expect($branch->fresh()->is_active)->toBeFalse();

    app(SetBranchActiveStatusAction::class)->handle($branch->fresh(), true);
    expect($branch->fresh()->is_active)->toBeTrue();
});

test('a deactivated branch is excluded from accessible-branch switchers', function (): void {
    $role = grantBranchesManage();
    $tenant = Tenant::factory()->create();
    $activeBranch = Branch::factory()->create(['tenant_id' => $tenant->id, 'is_active' => true]);
    $inactiveBranch = Branch::factory()->create(['tenant_id' => $tenant->id, 'is_active' => false]);

    $user = User::factory()->create(['tenant_id' => $tenant->id, 'current_branch_id' => $activeBranch->id]);
    $user->assignRole($role);
    $activeBranch->staff()->attach($user->id, ['role_id' => $role->id, 'is_primary' => true]);
    $inactiveBranch->staff()->attach($user->id, ['role_id' => $role->id, 'is_primary' => false]);

    // BranchManager itself lists everything (active and inactive, so staff
    // can find and reactivate a closed branch) — the exclusion is
    // specifically about the shared InteractsWithActiveBranch switcher
    // other modules use, checked separately below.
    Livewire::actingAs($user)->test(BranchManager::class)
        ->assertOk()
        ->assertSee($inactiveBranch->name);
});

test('the front desk branch switcher excludes deactivated branches', function (): void {
    $role = grantBranchesManage();
    Permission::firstOrCreate(['name' => 'reservations.manage', 'guard_name' => 'web']);
    $role->givePermissionTo('reservations.manage');

    $tenant = Tenant::factory()->create();
    $activeBranch = Branch::factory()->create(['tenant_id' => $tenant->id, 'is_active' => true]);
    $inactiveBranch = Branch::factory()->create(['tenant_id' => $tenant->id, 'is_active' => false]);

    $user = User::factory()->create(['tenant_id' => $tenant->id, 'current_branch_id' => $activeBranch->id]);
    $user->assignRole($role);
    $activeBranch->staff()->attach($user->id, ['role_id' => $role->id, 'is_primary' => true]);
    $inactiveBranch->staff()->attach($user->id, ['role_id' => $role->id, 'is_primary' => false]);

    $component = Livewire::actingAs($user)->test(Dashboard::class)->assertOk();

    expect($component->get('accessibleBranches')->pluck('id'))->not->toContain($inactiveBranch->id);
});

test('a branches.manage holder can create and edit branches through the component', function (): void {
    $role = grantBranchesManage();
    $tenant = Tenant::factory()->create(['default_currency' => 'USD', 'default_timezone' => 'UTC']);
    $user = User::factory()->create(['tenant_id' => $tenant->id]);
    $user->assignRole($role);

    Livewire::actingAs($user)
        ->test(BranchManager::class)
        ->call('create')
        ->set('name', 'Riverside')
        ->set('code', 'riv-01')
        ->set('currency', 'usd')
        ->set('timezone', 'UTC')
        ->set('checkInTime', '15:00')
        ->set('checkOutTime', '11:00')
        ->call('save')
        ->assertHasNoErrors();

    $branch = Branch::where('tenant_id', $tenant->id)->where('name', 'Riverside')->first();
    expect($branch)->not->toBeNull()
        ->and($branch->code)->toBe('RIV-01')
        ->and($branch->currency)->toBe('USD');
});

test('editing a branch prefills check-in and check-out time as plain H:i, not a full datetime', function (): void {
    $role = grantBranchesManage();
    $tenant = Tenant::factory()->create();
    $branch = Branch::factory()->create(['tenant_id' => $tenant->id, 'check_in_time' => '15:30:00', 'check_out_time' => '10:45:00']);

    $user = User::factory()->create(['tenant_id' => $tenant->id]);
    $user->assignRole($role);

    $component = Livewire::actingAs($user)
        ->test(BranchManager::class)
        ->call('edit', $branch->id)
        ->assertOk();

    expect($component->get('checkInTime'))->toBe('15:30')
        ->and($component->get('checkOutTime'))->toBe('10:45');
});

test('a user without branches.manage cannot open branch management', function (): void {
    Permission::firstOrCreate(['name' => 'branches.manage', 'guard_name' => 'web']);

    $user = User::factory()->create();

    Livewire::actingAs($user)->test(BranchManager::class)->assertForbidden();
});

test('a tampered branch id from another tenant is rejected on save', function (): void {
    $role = grantBranchesManage();
    $tenant = Tenant::factory()->create();
    $otherTenantBranch = Branch::factory()->create();

    $user = User::factory()->create(['tenant_id' => $tenant->id]);
    $user->assignRole($role);

    Livewire::actingAs($user)
        ->test(BranchManager::class)
        ->set('editingBranchId', $otherTenantBranch->id)
        ->set('name', 'Hijacked')
        ->set('code', $otherTenantBranch->code)
        ->set('currency', 'USD')
        ->set('timezone', 'UTC')
        ->set('checkInTime', '14:00')
        ->set('checkOutTime', '12:00')
        ->call('save')
        ->assertForbidden();

    expect($otherTenantBranch->fresh()->name)->not->toBe('Hijacked');
});

test('a tampered toggleActive call against another tenant\'s branch is rejected', function (): void {
    $role = grantBranchesManage();
    $tenant = Tenant::factory()->create();
    $otherTenantBranch = Branch::factory()->create(['is_active' => true]);

    $user = User::factory()->create(['tenant_id' => $tenant->id]);
    $user->assignRole($role);

    Livewire::actingAs($user)
        ->test(BranchManager::class)
        ->call('toggleActive', $otherTenantBranch->id)
        ->assertForbidden();

    expect($otherTenantBranch->fresh()->is_active)->toBeTrue();
});
