<?php

declare(strict_types=1);

use App\Livewire\Guests\GuestManager;
use App\Models\Guest;
use App\Models\Tenant;
use App\Models\User;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

beforeEach(function (): void {
    Permission::firstOrCreate(['name' => 'guests.manage', 'guard_name' => 'web']);
    Permission::firstOrCreate(['name' => 'guests.blacklist', 'guard_name' => 'web']);
    $role = Role::firstOrCreate(['name' => 'Receptionist', 'guard_name' => 'web']);
    $role->givePermissionTo('guests.manage');

    $this->tenant = Tenant::factory()->create();
    $this->staff = User::factory()->create(['tenant_id' => $this->tenant->id]);
    $this->staff->assignRole($role);
});

test('a staff member can create a guest', function (): void {
    Livewire::actingAs($this->staff)
        ->test(GuestManager::class)
        ->call('create')
        ->set('firstName', 'Ada')
        ->set('lastName', 'Lovelace')
        ->set('email', 'ada@example.com')
        ->call('save')
        ->assertHasNoErrors();

    expect(Guest::where('email', 'ada@example.com')->exists())->toBeTrue();
});

test('searching filters the guest list by name', function (): void {
    Guest::factory()->create(['tenant_id' => $this->tenant->id, 'first_name' => 'Grace', 'last_name' => 'Hopper']);
    Guest::factory()->create(['tenant_id' => $this->tenant->id, 'first_name' => 'Alan', 'last_name' => 'Turing']);

    Livewire::actingAs($this->staff)
        ->test(GuestManager::class)
        ->set('search', 'Hopper')
        ->assertSee('Grace')
        ->assertDontSee('Alan');
});

test('toggling VIP flips the guest flag', function (): void {
    $guest = Guest::factory()->create(['tenant_id' => $this->tenant->id]);

    Livewire::actingAs($this->staff)
        ->test(GuestManager::class)
        ->call('toggleVip', $guest->id);

    expect($guest->fresh()->flag->value)->toBe('vip');
});

test('a staff member with guests.blacklist can blacklist a guest with a reason', function (): void {
    $securityRole = Role::firstOrCreate(['name' => 'Security Officer', 'guard_name' => 'web']);
    $securityRole->givePermissionTo(['guests.manage', 'guests.blacklist']);
    $security = User::factory()->create(['tenant_id' => $this->tenant->id]);
    $security->assignRole($securityRole);

    $guest = Guest::factory()->create(['tenant_id' => $this->tenant->id]);

    Livewire::actingAs($security)
        ->test(GuestManager::class)
        ->call('startBlacklist', $guest->id)
        ->set('blacklistReason', 'Repeated property damage across two stays.')
        ->call('confirmBlacklist')
        ->assertHasNoErrors();

    $guest->refresh();
    expect($guest->flag->value)->toBe('blacklisted')
        ->and($guest->blacklist_reason)->toBe('Repeated property damage across two stays.');
});

test('blacklisting without a reason is rejected', function (): void {
    $securityRole = Role::firstOrCreate(['name' => 'Security Officer', 'guard_name' => 'web']);
    $securityRole->givePermissionTo(['guests.manage', 'guests.blacklist']);
    $security = User::factory()->create(['tenant_id' => $this->tenant->id]);
    $security->assignRole($securityRole);

    $guest = Guest::factory()->create(['tenant_id' => $this->tenant->id]);

    Livewire::actingAs($security)
        ->test(GuestManager::class)
        ->call('startBlacklist', $guest->id)
        ->call('confirmBlacklist')
        ->assertHasErrors(['blacklistReason']);

    expect($guest->fresh()->flag->value)->toBe('none');
});

test('removing a guest from the blacklist clears the flag and reason', function (): void {
    $securityRole = Role::firstOrCreate(['name' => 'Security Officer', 'guard_name' => 'web']);
    $securityRole->givePermissionTo(['guests.manage', 'guests.blacklist']);
    $security = User::factory()->create(['tenant_id' => $this->tenant->id]);
    $security->assignRole($securityRole);

    $guest = Guest::factory()->blacklisted()->create(['tenant_id' => $this->tenant->id]);

    Livewire::actingAs($security)
        ->test(GuestManager::class)
        ->call('removeFromBlacklist', $guest->id);

    $guest->refresh();
    expect($guest->flag->value)->toBe('none')
        ->and($guest->blacklist_reason)->toBeNull();
});

test('a staff member without guests.blacklist cannot blacklist a guest', function (): void {
    $guest = Guest::factory()->create(['tenant_id' => $this->tenant->id]);

    Livewire::actingAs($this->staff)
        ->test(GuestManager::class)
        ->call('startBlacklist', $guest->id)
        ->assertForbidden();

    expect($guest->fresh()->flag->value)->toBe('none');
});

test('the blacklisted-only filter shows only blacklisted guests', function (): void {
    $blacklisted = Guest::factory()->blacklisted()->create(['tenant_id' => $this->tenant->id, 'first_name' => 'Blake', 'last_name' => 'Listed']);
    $regular = Guest::factory()->create(['tenant_id' => $this->tenant->id, 'first_name' => 'Regina', 'last_name' => 'Ular']);

    Livewire::actingAs($this->staff)
        ->test(GuestManager::class)
        ->set('blacklistedOnly', true)
        ->assertSee('Blake')
        ->assertDontSee('Regina');
});
