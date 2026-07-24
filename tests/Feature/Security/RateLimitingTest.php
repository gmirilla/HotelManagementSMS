<?php

declare(strict_types=1);

/**
 * NFR-SEC-004: authentication endpoints must be rate-limited. The web login
 * flow's own account-lockout behavior (5 failed attempts) is covered in
 * LoginTest.php; this covers the API's dedicated 'api-auth' limiter
 * (AppServiceProvider::configureApiRateLimiting(), 10 requests/minute per
 * IP) introduced alongside the REST API in Deliverable 8.
 */

use App\Models\User;

test('the API login endpoint is rate-limited per IP', function (): void {
    $user = User::factory()->create(['password' => bcrypt('secret1234')]);

    // The limiter is keyed by IP, not by which account is being attempted —
    // exhaust it with wrong-password attempts against the same account.
    for ($i = 0; $i < 10; $i++) {
        $this->postJson('/api/v1/auth/login', [
            'email' => $user->email,
            'password' => 'wrong-password',
            'device_name' => "device-{$i}",
        ]);
    }

    $response = $this->postJson('/api/v1/auth/login', [
        'email' => $user->email,
        'password' => 'wrong-password',
        'device_name' => 'device-final',
    ]);

    $response->assertStatus(429)
        ->assertJsonPath('error.code', 'too_many_requests');
});

test('a correct-password login still counts toward the API rate limit', function (): void {
    $user = User::factory()->create(['password' => bcrypt('secret1234')]);

    for ($i = 0; $i < 10; $i++) {
        $this->postJson('/api/v1/auth/login', [
            'email' => $user->email,
            'password' => 'secret1234',
            'device_name' => "device-{$i}",
        ]);
    }

    $response = $this->postJson('/api/v1/auth/login', [
        'email' => $user->email,
        'password' => 'secret1234',
        'device_name' => 'device-final',
    ]);

    $response->assertStatus(429);
});
