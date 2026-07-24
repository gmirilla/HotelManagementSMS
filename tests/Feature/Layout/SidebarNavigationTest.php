<?php

declare(strict_types=1);

use App\Models\User;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

test('a bare user sees ungated navigation but not the administration section', function (): void {
    $user = User::factory()->create();

    $this->actingAs($user)->get(route('dashboard'))
        ->assertOk()
        ->assertSee('Front Desk')
        ->assertSee('Reservations')
        ->assertSee('Accounting')
        ->assertDontSee('Administration');
});

test('a users.view holder sees the Users link but not Appearance', function (): void {
    Permission::firstOrCreate(['name' => 'users.view', 'guard_name' => 'web']);
    $role = Role::firstOrCreate(['name' => 'Boundary Sidebar Viewer', 'guard_name' => 'web']);
    $role->givePermissionTo('users.view');

    $user = User::factory()->create();
    $user->assignRole($role);

    $this->actingAs($user)->get(route('dashboard'))
        ->assertOk()
        ->assertSee('Administration')
        ->assertSee('Users')
        ->assertDontSee('Appearance');
});

test('a settings.manage holder sees the Appearance link', function (): void {
    Permission::firstOrCreate(['name' => 'settings.manage', 'guard_name' => 'web']);
    $role = Role::firstOrCreate(['name' => 'Boundary Sidebar Settings Manager', 'guard_name' => 'web']);
    $role->givePermissionTo('settings.manage');

    $user = User::factory()->create();
    $user->assignRole($role);

    $this->actingAs($user)->get(route('dashboard'))
        ->assertOk()
        ->assertSee('Administration')
        ->assertSee('Appearance');
});
