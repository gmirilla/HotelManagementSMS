<?php

declare(strict_types=1);

/**
 * Real-browser smoke tests for a few golden-path flows, per the original
 * Deliverable 9 request for browser/E2E coverage. See browserTest() in
 * tests/Pest.php for why every test here is wrapped in a skip-on-
 * unavailable-browser guard.
 */

use App\Models\Branch;
use App\Models\User;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

test('the login page renders in a real browser', function (): void {
    browserTest(fn () => $this->visit('/login')->assertSee('Log in'));
});

test('a user can log in through a real browser and land on the dashboard', function (): void {
    $user = User::factory()->create(['password' => bcrypt('secret1234')]);

    browserTest(function () use ($user) {
        $this->visit('/login')
            ->fill('email', $user->email)
            ->fill('password', 'secret1234')
            ->press('Log in')
            ->assertSee('Welcome back');
    });
});

test('an authenticated user sees their module navigation on the dashboard', function (): void {
    Permission::firstOrCreate(['name' => 'reservations.view', 'guard_name' => 'web']);
    $role = Role::firstOrCreate(['name' => 'Browser Smoke Test Role', 'guard_name' => 'web']);
    $role->givePermissionTo('reservations.view');

    $branch = Branch::factory()->create();
    $user = User::factory()->create(['tenant_id' => $branch->tenant_id, 'current_branch_id' => $branch->id]);
    $user->assignRole($role);
    $branch->staff()->attach($user->id, ['role_id' => $role->id, 'is_primary' => true]);

    browserTest(function () use ($user) {
        $this->actingAs($user)
            ->visit('/dashboard')
            ->assertSee('Front Desk')
            ->assertSee('Reservations');
    });
});
