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
