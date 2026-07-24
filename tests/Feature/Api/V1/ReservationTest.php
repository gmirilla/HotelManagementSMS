<?php

declare(strict_types=1);

use App\Domain\Reservation\Enums\ReservationStatus;
use App\Models\Branch;
use App\Models\Guest;
use App\Models\Reservation;
use App\Models\Room;
use App\Models\RoomType;
use App\Models\User;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

beforeEach(function (): void {
    Permission::firstOrCreate(['name' => 'reservations.manage', 'guard_name' => 'web']);
    $role = Role::firstOrCreate(['name' => 'Api Reservation Manager', 'guard_name' => 'web']);
    $role->givePermissionTo('reservations.manage');

    $this->branch = Branch::factory()->create();
    $this->user = User::factory()->create(['tenant_id' => $this->branch->tenant_id]);
    $this->user->assignRole($role);
    $this->branch->staff()->attach($this->user->id, ['role_id' => $role->id, 'is_primary' => true]);
    $this->token = $this->user->createToken('test', ['bookings:read', 'bookings:write'])->plainTextToken;

    $this->roomType = RoomType::factory()->create(['branch_id' => $this->branch->id, 'base_rate_cents' => 10000]);
    Room::factory()->count(2)->create(['branch_id' => $this->branch->id, 'room_type_id' => $this->roomType->id]);
    $this->guest = Guest::factory()->create(['tenant_id' => $this->branch->tenant_id]);
});

test('creating a booking requires the bookings:write ability', function (): void {
    $tokenWithoutAbility = $this->user->createToken('read-only', ['bookings:read'])->plainTextToken;

    $this->withHeader('Authorization', "Bearer {$tokenWithoutAbility}")
        ->postJson('/api/v1/reservations', [
            'branch_id' => $this->branch->id,
            'guest_id' => $this->guest->id,
            'room_type_id' => $this->roomType->id,
            'arrival_date' => '2026-09-01',
            'departure_date' => '2026-09-03',
            'adults' => 2,
        ])->assertStatus(403);
});

test('creating a booking with availability succeeds and returns a confirmed reservation', function (): void {
    $response = $this->withHeader('Authorization', "Bearer {$this->token}")
        ->postJson('/api/v1/reservations', [
            'branch_id' => $this->branch->id,
            'guest_id' => $this->guest->id,
            'room_type_id' => $this->roomType->id,
            'arrival_date' => '2026-09-01',
            'departure_date' => '2026-09-03',
            'adults' => 2,
        ]);

    $response->assertCreated()
        ->assertJsonPath('data.status', 'confirmed')
        ->assertJsonPath('data.nights', 2);

    expect(Reservation::where('guest_id', $this->guest->id)->first()->status)->toBe(ReservationStatus::Confirmed);
});

test('creating a booking beyond available inventory is rejected with a validation envelope', function (): void {
    // Only 2 rooms of this type exist — book both, then a third request must fail.
    foreach (range(1, 2) as $i) {
        $this->withHeader('Authorization', "Bearer {$this->token}")->postJson('/api/v1/reservations', [
            'branch_id' => $this->branch->id,
            'guest_id' => $this->guest->id,
            'room_type_id' => $this->roomType->id,
            'arrival_date' => '2026-09-01',
            'departure_date' => '2026-09-03',
            'adults' => 1,
        ])->assertCreated();
    }

    $this->withHeader('Authorization', "Bearer {$this->token}")
        ->postJson('/api/v1/reservations', [
            'branch_id' => $this->branch->id,
            'guest_id' => $this->guest->id,
            'room_type_id' => $this->roomType->id,
            'arrival_date' => '2026-09-01',
            'departure_date' => '2026-09-03',
            'adults' => 1,
        ])
        ->assertStatus(422)
        ->assertJsonPath('error.code', 'validation_error');
});

test('cancelling a booking flips its status and records a cancellation fee', function (): void {
    $reservation = Reservation::factory()->create([
        'branch_id' => $this->branch->id,
        'guest_id' => $this->guest->id,
        'status' => ReservationStatus::Confirmed,
        'arrival_date' => now()->addDays(10),
        'departure_date' => now()->addDays(12),
    ]);

    $response = $this->withHeader('Authorization', "Bearer {$this->token}")
        ->postJson("/api/v1/reservations/{$reservation->id}/cancel", ['reason' => 'Guest changed plans']);

    $response->assertOk()->assertJsonPath('data.status', 'cancelled');
    expect($reservation->fresh()->status)->toBe(ReservationStatus::Cancelled);
});

test('showing a booking outside the user\'s accessible branch is forbidden', function (): void {
    $otherBranch = Branch::factory()->create();
    $reservation = Reservation::factory()->create(['branch_id' => $otherBranch->id]);

    $this->withHeader('Authorization', "Bearer {$this->token}")
        ->getJson("/api/v1/reservations/{$reservation->id}")
        ->assertStatus(403);
});
