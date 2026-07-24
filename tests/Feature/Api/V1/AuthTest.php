<?php

declare(strict_types=1);

use App\Models\Branch;
use App\Models\User;
use PragmaRX\Google2FA\Google2FA;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

test('logging in with valid credentials returns a token and abilities derived from permissions', function (): void {
    $branch = Branch::factory()->create();
    Permission::firstOrCreate(['name' => 'reservations.view', 'guard_name' => 'web']);
    Permission::firstOrCreate(['name' => 'guests.manage', 'guard_name' => 'web']);
    $role = Role::firstOrCreate(['name' => 'Api Test Role', 'guard_name' => 'web']);
    $role->givePermissionTo(['reservations.view', 'guests.manage']);

    $user = User::factory()->create(['tenant_id' => $branch->tenant_id, 'password' => bcrypt('secret1234')]);
    $user->assignRole($role);

    $response = $this->postJson('/api/v1/auth/login', [
        'email' => $user->email,
        'password' => 'secret1234',
        'device_name' => 'test-device',
    ]);

    $response->assertOk()
        ->assertJsonPath('data.user.id', $user->id)
        ->assertJsonStructure(['data' => ['user', 'token', 'abilities']]);

    expect($response->json('data.abilities'))
        ->toContain('bookings:read')
        ->toContain('guests:write')
        ->toContain('rooms:read');
});

test('logging in with an invalid password is rejected with the validation error envelope', function (): void {
    $user = User::factory()->create(['password' => bcrypt('secret1234')]);

    $response = $this->postJson('/api/v1/auth/login', [
        'email' => $user->email,
        'password' => 'wrong-password',
        'device_name' => 'test-device',
    ]);

    $response->assertStatus(422)
        ->assertJsonPath('error.code', 'validation_error');
});

test('an MFA-enabled account is rejected without a valid code', function (): void {
    $secret = app(Google2FA::class)->generateSecretKey();
    $user = User::factory()->create(['password' => bcrypt('secret1234'), 'mfa_enabled' => true, 'mfa_secret' => $secret]);

    $response = $this->postJson('/api/v1/auth/login', [
        'email' => $user->email,
        'password' => 'secret1234',
        'device_name' => 'test-device',
    ]);

    $response->assertStatus(422)->assertJsonPath('error.code', 'validation_error');
});

test('an MFA-enabled account authenticates with a valid TOTP code', function (): void {
    $google2fa = app(Google2FA::class);
    $secret = $google2fa->generateSecretKey();
    $user = User::factory()->create(['password' => bcrypt('secret1234'), 'mfa_enabled' => true, 'mfa_secret' => $secret]);

    $response = $this->postJson('/api/v1/auth/login', [
        'email' => $user->email,
        'password' => 'secret1234',
        'device_name' => 'test-device',
        'mfa_code' => $google2fa->getCurrentOtp($secret),
    ]);

    $response->assertOk()->assertJsonPath('data.user.id', $user->id);
});

test('logging out deletes the current access token from the database', function (): void {
    $user = User::factory()->create();
    $tokenResult = $user->createToken('test-device', ['rooms:read']);

    $this->withHeader('Authorization', "Bearer {$tokenResult->plainTextToken}")
        ->postJson('/api/v1/auth/logout')
        ->assertOk();

    // Asserted against the database rather than a second HTTP round-trip:
    // Laravel's auth guard caches the resolved user on the guard instance
    // within a single test's container, so a follow-up request would keep
    // authenticating even after the token row is gone.
    expect($user->tokens()->whereKey($tokenResult->accessToken->id)->exists())->toBeFalse();
});

test('the me endpoint returns the authenticated user profile', function (): void {
    $user = User::factory()->create();
    $token = $user->createToken('test-device', ['rooms:read'])->plainTextToken;

    $this->withHeader('Authorization', "Bearer {$token}")
        ->getJson('/api/v1/auth/me')
        ->assertOk()
        ->assertJsonPath('data.id', $user->id)
        ->assertJsonPath('data.email', $user->email);
});
