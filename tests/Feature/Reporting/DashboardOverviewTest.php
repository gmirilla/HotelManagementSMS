<?php

declare(strict_types=1);

use App\Domain\Room\Enums\RoomStatus;
use App\Livewire\Reporting\DashboardOverview;
use App\Models\Branch;
use App\Models\Room;
use App\Models\User;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

test('a bare authenticated user with no permissions can still render the dashboard', function (): void {
    $user = User::factory()->create();

    Livewire::actingAs($user)
        ->test(DashboardOverview::class)
        ->assertOk()
        ->assertDontSee('Occupancy')
        ->assertDontSee('Outstanding invoices');
});

test('a Guest-role portal user does not see the internal KPI dashboard', function (): void {
    $guestRole = Role::firstOrCreate(['name' => 'Guest', 'guard_name' => 'web']);
    $user = User::factory()->create();
    $user->assignRole($guestRole);

    Livewire::actingAs($user)
        ->test(DashboardOverview::class)
        ->assertOk()
        ->assertDontSee('Occupancy')
        ->assertDontSee('Revenue trend');
});

test('a reports.view holder sees the KPI section with the right figures', function (): void {
    Permission::firstOrCreate(['name' => 'reports.view', 'guard_name' => 'web']);
    $role = Role::firstOrCreate(['name' => 'Boundary Reports Viewer', 'guard_name' => 'web']);
    $role->givePermissionTo('reports.view');

    $branch = Branch::factory()->create();
    Room::factory()->create(['branch_id' => $branch->id, 'status' => RoomStatus::Occupied, 'is_active' => true]);
    Room::factory(3)->create(['branch_id' => $branch->id, 'status' => RoomStatus::VacantClean, 'is_active' => true]);

    $user = User::factory()->create(['tenant_id' => $branch->tenant_id, 'current_branch_id' => $branch->id]);
    $user->assignRole($role);
    $branch->staff()->attach($user->id, ['role_id' => $role->id, 'is_primary' => true]);

    $component = Livewire::actingAs($user)
        ->test(DashboardOverview::class)
        ->assertOk()
        ->assertSee('Occupancy')
        ->assertSee('Outstanding invoices')
        ->assertSee('Revenue trend')
        ->assertSee('Room status');

    expect($component->get('occupancyRate'))->toBe(25.0);
});

test('switching branches recomputes the KPIs for the newly selected branch', function (): void {
    Permission::firstOrCreate(['name' => 'reports.view', 'guard_name' => 'web']);
    $role = Role::firstOrCreate(['name' => 'Boundary Reports Viewer 2', 'guard_name' => 'web']);
    $role->givePermissionTo('reports.view');

    $branchA = Branch::factory()->create();
    $branchB = Branch::factory()->create(['tenant_id' => $branchA->tenant_id]);
    Room::factory()->create(['branch_id' => $branchA->id, 'status' => RoomStatus::Occupied, 'is_active' => true]);
    Room::factory(3)->create(['branch_id' => $branchB->id, 'status' => RoomStatus::VacantClean, 'is_active' => true]);

    $user = User::factory()->create(['tenant_id' => $branchA->tenant_id, 'current_branch_id' => $branchA->id]);
    $user->assignRole($role);
    $branchA->staff()->attach($user->id, ['role_id' => $role->id, 'is_primary' => true]);
    $branchB->staff()->attach($user->id, ['role_id' => $role->id, 'is_primary' => false]);

    $component = Livewire::actingAs($user)->test(DashboardOverview::class)->assertOk();

    expect($component->get('occupancyRate'))->toBe(100.0);

    $component->set('branchId', $branchB->id)->assertOk();

    expect($component->get('occupancyRate'))->toBe(0.0);
});
