<?php

declare(strict_types=1);

use App\Models\Branch;
use App\Models\Guest;
use App\Models\User;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

beforeEach(function (): void {
    Permission::firstOrCreate(['name' => 'guests.manage', 'guard_name' => 'web']);
    $role = Role::firstOrCreate(['name' => 'Api Guest Manager', 'guard_name' => 'web']);
    $role->givePermissionTo('guests.manage');

    $this->branch = Branch::factory()->create();
    $this->user = User::factory()->create(['tenant_id' => $this->branch->tenant_id]);
    $this->user->assignRole($role);
    $this->token = $this->user->createToken('test', ['guests:read', 'guests:write'])->plainTextToken;
});

test('creating a guest requires the guests:write ability', function (): void {
    $tokenWithoutAbility = $this->user->createToken('no-ability', ['rooms:read'])->plainTextToken;

    $this->withHeader('Authorization', "Bearer {$tokenWithoutAbility}")
        ->postJson('/api/v1/guests', ['first_name' => 'Jane', 'last_name' => 'Doe'])
        ->assertStatus(403);
});

test('creating a guest with valid data returns 201 and the created resource', function (): void {
    $response = $this->withHeader('Authorization', "Bearer {$this->token}")
        ->postJson('/api/v1/guests', ['first_name' => 'Jane', 'last_name' => 'Doe', 'email' => 'jane@example.test']);

    $response->assertCreated()
        ->assertJsonPath('data.first_name', 'Jane')
        ->assertJsonPath('data.last_name', 'Doe');

    $this->assertDatabaseHas('guests', ['first_name' => 'Jane', 'tenant_id' => $this->user->tenant_id]);
});

test('creating a guest without a required field returns the validation error envelope', function (): void {
    $this->withHeader('Authorization', "Bearer {$this->token}")
        ->postJson('/api/v1/guests', ['first_name' => 'Jane'])
        ->assertStatus(422)
        ->assertJsonPath('error.code', 'validation_error')
        ->assertJsonPath('error.errors.last_name.0', 'The last name field is required.');
});

test('searching guests filters by name', function (): void {
    Guest::factory()->create(['tenant_id' => $this->user->tenant_id, 'first_name' => 'Zendaya', 'last_name' => 'Smith']);
    Guest::factory()->create(['tenant_id' => $this->user->tenant_id, 'first_name' => 'Marcus', 'last_name' => 'Webb']);

    $response = $this->withHeader('Authorization', "Bearer {$this->token}")
        ->getJson('/api/v1/guests?search=Zendaya');

    $response->assertOk();
    expect($response->json('data'))->toHaveCount(1)
        ->and($response->json('data.0.first_name'))->toBe('Zendaya');
});

test('showing a guest from a different tenant is forbidden', function (): void {
    $otherGuest = Guest::factory()->create();

    $this->withHeader('Authorization', "Bearer {$this->token}")
        ->getJson("/api/v1/guests/{$otherGuest->id}")
        ->assertStatus(403);
});

test('updating a guest persists the changes', function (): void {
    $guest = Guest::factory()->create(['tenant_id' => $this->user->tenant_id, 'phone' => '000']);

    $this->withHeader('Authorization', "Bearer {$this->token}")
        ->patchJson("/api/v1/guests/{$guest->id}", ['phone' => '+1-555-0100'])
        ->assertOk()
        ->assertJsonPath('data.phone', '+1-555-0100');

    expect($guest->fresh()->phone)->toBe('+1-555-0100');
});
