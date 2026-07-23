<?php

declare(strict_types=1);

use App\Livewire\Rooms\RoomTypeManager;
use App\Models\Amenity;
use App\Models\Branch;
use App\Models\RoomType;
use App\Models\User;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

beforeEach(function (): void {
    Permission::firstOrCreate(['name' => 'rooms.manage', 'guard_name' => 'web']);
    $role = Role::firstOrCreate(['name' => 'Branch Manager', 'guard_name' => 'web']);
    $role->givePermissionTo('rooms.manage');

    $this->branch = Branch::factory()->create();
    $this->staff = User::factory()->create(['tenant_id' => $this->branch->tenant_id, 'current_branch_id' => $this->branch->id]);
    $this->staff->assignRole($role);
    $this->branch->staff()->attach($this->staff->id, ['role_id' => $role->id, 'is_primary' => true]);
});

test('a manager can create a room type with amenities', function (): void {
    $amenity = Amenity::factory()->create();

    Livewire::actingAs($this->staff)
        ->test(RoomTypeManager::class)
        ->call('create')
        ->set('name', 'Deluxe King')
        ->set('baseRate', '199.99')
        ->set('baseCapacityAdults', 2)
        ->set('baseCapacityChildren', 1)
        ->set('selectedAmenities', [$amenity->id])
        ->call('save')
        ->assertHasNoErrors();

    $roomType = RoomType::where('name', 'Deluxe King')->firstOrFail();
    expect($roomType->base_rate_cents)->toBe(19999)
        ->and($roomType->amenities)->toHaveCount(1);
});

test('creating a room type without a name fails validation', function (): void {
    Livewire::actingAs($this->staff)
        ->test(RoomTypeManager::class)
        ->call('create')
        ->set('baseRate', '100')
        ->call('save')
        ->assertHasErrors(['name']);
});

test('a user without rooms.manage cannot create a room type', function (): void {
    $unprivileged = User::factory()->create(['tenant_id' => $this->branch->tenant_id, 'current_branch_id' => $this->branch->id]);

    Livewire::actingAs($unprivileged)
        ->test(RoomTypeManager::class)
        ->call('create')
        ->assertForbidden();
});
