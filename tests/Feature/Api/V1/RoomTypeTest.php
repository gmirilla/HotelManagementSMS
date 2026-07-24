<?php

declare(strict_types=1);

use App\Models\Branch;
use App\Models\Reservation;
use App\Models\ReservationRoom;
use App\Models\Room;
use App\Models\RoomType;
use App\Models\User;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

beforeEach(function (): void {
    Permission::firstOrCreate(['name' => 'rooms.view', 'guard_name' => 'web']);
    $role = Role::firstOrCreate(['name' => 'Api Room Viewer', 'guard_name' => 'web']);
    $role->givePermissionTo('rooms.view');

    $this->branch = Branch::factory()->create();
    $this->user = User::factory()->create(['tenant_id' => $this->branch->tenant_id]);
    $this->user->assignRole($role);
    $this->branch->staff()->attach($this->user->id, ['role_id' => $role->id, 'is_primary' => true]);
    $this->token = $this->user->createToken('test', ['rooms:read'])->plainTextToken;
});

test('listing room types requires the rooms:read ability', function (): void {
    RoomType::factory()->count(2)->create(['branch_id' => $this->branch->id]);
    $tokenWithoutAbility = $this->user->createToken('no-ability', ['guests:read'])->plainTextToken;

    $this->withHeader('Authorization', "Bearer {$tokenWithoutAbility}")
        ->getJson("/api/v1/room-types?branch_id={$this->branch->id}")
        ->assertStatus(403)
        ->assertJsonPath('error.code', 'forbidden');
});

test('listing room types returns only active room types for the branch', function (): void {
    RoomType::factory()->create(['branch_id' => $this->branch->id, 'is_active' => true]);
    RoomType::factory()->create(['branch_id' => $this->branch->id, 'is_active' => false]);

    $response = $this->withHeader('Authorization', "Bearer {$this->token}")
        ->getJson("/api/v1/room-types?branch_id={$this->branch->id}");

    $response->assertOk();
    expect($response->json('data'))->toHaveCount(1);
});

test('availability reflects rooms already booked for the requested date range', function (): void {
    $roomType = RoomType::factory()->create(['branch_id' => $this->branch->id]);
    Room::factory()->count(3)->create(['branch_id' => $this->branch->id, 'room_type_id' => $roomType->id]);

    $reservation = Reservation::factory()->create(['branch_id' => $this->branch->id, 'arrival_date' => '2026-09-01', 'departure_date' => '2026-09-05']);
    ReservationRoom::factory()->create(['reservation_id' => $reservation->id, 'room_type_id' => $roomType->id]);

    $response = $this->withHeader('Authorization', "Bearer {$this->token}")
        ->getJson("/api/v1/room-types/{$roomType->id}/availability?arrival_date=2026-09-02&departure_date=2026-09-03");

    $response->assertOk()->assertJsonPath('data.available_rooms', 2);
});
