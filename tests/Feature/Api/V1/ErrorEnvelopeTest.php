<?php

declare(strict_types=1);

/**
 * FR-API-003: every API error must share one envelope shape —
 * {"error": {"code", "message", "errors"?}} — regardless of which exception
 * produced it. This is a regression guard specifically because Laravel
 * rewrites several exception types (AuthorizationException →
 * AccessDeniedHttpException, ModelNotFoundException → NotFoundHttpException)
 * before any custom render() callback sees them, which is easy to miss when
 * writing that callback — see ApiExceptionRenderer's docblock.
 */

use App\Models\Guest;
use App\Models\User;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

test('an unauthenticated request returns a 401 envelope', function (): void {
    $this->getJson('/api/v1/auth/me')
        ->assertStatus(401)
        ->assertJsonStructure(['error' => ['code', 'message']])
        ->assertJsonPath('error.code', 'unauthenticated');
});

test('a request missing the required token ability returns a 403 envelope', function (): void {
    $user = User::factory()->create();
    $token = $user->createToken('test', [])->plainTextToken;

    $this->withHeader('Authorization', "Bearer {$token}")
        ->getJson('/api/v1/room-types?branch_id=1')
        ->assertStatus(403)
        ->assertJsonStructure(['error' => ['code', 'message']])
        ->assertJsonPath('error.code', 'forbidden');
});

test('a policy-denied request (not just a missing ability) also returns a 403 envelope', function (): void {
    Permission::firstOrCreate(['name' => 'guests.manage', 'guard_name' => 'web']);
    $role = Role::firstOrCreate(['name' => 'Error Envelope Guest Manager', 'guard_name' => 'web']);
    $role->givePermissionTo('guests.manage');

    $user = User::factory()->create();
    $user->assignRole($role);
    $token = $user->createToken('test', ['guests:read'])->plainTextToken;

    // A guest from a different tenant — the token ability check passes, but
    // GuestPolicy::view() denies on tenant mismatch (AuthorizationException,
    // not a Sanctum MissingAbilityException).
    $otherTenantGuest = Guest::factory()->create();

    $this->withHeader('Authorization', "Bearer {$token}")
        ->getJson("/api/v1/guests/{$otherTenantGuest->id}")
        ->assertStatus(403)
        ->assertJsonStructure(['error' => ['code', 'message']])
        ->assertJsonPath('error.code', 'forbidden');
});

test('a missing resource returns a 404 envelope', function (): void {
    $user = User::factory()->create();
    $token = $user->createToken('test', ['guests:read'])->plainTextToken;

    $this->withHeader('Authorization', "Bearer {$token}")
        ->getJson('/api/v1/guests/999999')
        ->assertStatus(404)
        ->assertJsonStructure(['error' => ['code', 'message']])
        ->assertJsonPath('error.code', 'not_found');
});

test('a validation failure returns a 422 envelope with field-level errors', function (): void {
    Permission::firstOrCreate(['name' => 'guests.manage', 'guard_name' => 'web']);
    $role = Role::firstOrCreate(['name' => 'Error Envelope Guest Manager 2', 'guard_name' => 'web']);
    $role->givePermissionTo('guests.manage');

    $user = User::factory()->create();
    $user->assignRole($role);
    $token = $user->createToken('test', ['guests:write'])->plainTextToken;

    $this->withHeader('Authorization', "Bearer {$token}")
        ->postJson('/api/v1/guests', [])
        ->assertStatus(422)
        ->assertJsonStructure(['error' => ['code', 'message', 'errors']])
        ->assertJsonPath('error.code', 'validation_error');
});
