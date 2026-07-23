<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Support\Facades\Hash;

test('the login screen renders', function (): void {
    $this->get(route('login'))->assertOk();
});

test('a user can authenticate with correct credentials', function (): void {
    $user = User::factory()->create(['password' => Hash::make('correct-password')]);

    $response = $this->post(route('login'), [
        'email' => $user->email,
        'password' => 'correct-password',
    ]);

    $this->assertAuthenticatedAs($user);
    $response->assertRedirect(route('dashboard'));
});

test('a user cannot authenticate with an incorrect password', function (): void {
    $user = User::factory()->create(['password' => Hash::make('correct-password')]);

    $response = $this->post(route('login'), [
        'email' => $user->email,
        'password' => 'wrong-password',
    ]);

    $response->assertSessionHasErrors('email');
    $this->assertGuest();
});

test('an account locks after the configured number of failed attempts', function (): void {
    config(['security.max_login_attempts' => 3, 'security.lockout_minutes' => 15]);

    $user = User::factory()->create(['password' => Hash::make('correct-password')]);

    foreach (range(1, 3) as $attempt) {
        $this->post(route('login'), ['email' => $user->email, 'password' => 'wrong-password']);
    }

    expect($user->fresh()->isLocked())->toBeTrue();

    // Even the correct password is now rejected until the lockout expires.
    $response = $this->post(route('login'), ['email' => $user->email, 'password' => 'correct-password']);

    $response->assertSessionHasErrors('email');
    $this->assertGuest();
});

test('a successful login resets the failed attempt counter', function (): void {
    $user = User::factory()->create([
        'password' => Hash::make('correct-password'),
        'failed_login_attempts' => 2,
    ]);

    $this->post(route('login'), ['email' => $user->email, 'password' => 'correct-password']);

    expect($user->fresh()->failed_login_attempts)->toBe(0);
});

test('a user with MFA enabled is sent to the challenge screen instead of being logged in directly', function (): void {
    $user = User::factory()->create([
        'password' => Hash::make('correct-password'),
        'mfa_enabled' => true,
        'mfa_secret' => 'ADUMMYSECRETFORTESTINGONLY',
    ]);

    $response = $this->post(route('login'), ['email' => $user->email, 'password' => 'correct-password']);

    $response->assertRedirect(route('mfa.challenge'));
    $this->assertGuest();
    expect(session('mfa.user.id'))->toBe($user->id);
});
