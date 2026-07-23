<?php

declare(strict_types=1);

use App\Livewire\Rooms\RoomManager;
use App\Models\Branch;
use App\Models\Room;
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
    $this->roomType = RoomType::factory()->create(['branch_id' => $this->branch->id]);
    $this->staff = User::factory()->create(['tenant_id' => $this->branch->tenant_id, 'current_branch_id' => $this->branch->id]);
    $this->staff->assignRole($role);
    $this->branch->staff()->attach($this->staff->id, ['role_id' => $role->id, 'is_primary' => true]);
});

test('a manager can create a room and it defaults to vacant clean', function (): void {
    Livewire::actingAs($this->staff)
        ->test(RoomManager::class)
        ->call('create')
        ->set('roomNumber', '501')
        ->set('roomTypeId', $this->roomType->id)
        ->call('save')
        ->assertHasNoErrors();

    $room = Room::where('room_number', '501')->firstOrFail();
    expect($room->status->value)->toBe('vacant_clean');
});

test('the status filter only shows matching rooms', function (): void {
    Room::factory()->create(['branch_id' => $this->branch->id, 'room_type_id' => $this->roomType->id, 'status' => 'vacant_clean', 'room_number' => '101']);
    Room::factory()->create(['branch_id' => $this->branch->id, 'room_type_id' => $this->roomType->id, 'status' => 'out_of_order', 'room_number' => '102']);

    Livewire::actingAs($this->staff)
        ->test(RoomManager::class)
        ->set('statusFilter', 'out_of_order')
        ->assertSee('102')
        ->assertDontSee('101');
});
