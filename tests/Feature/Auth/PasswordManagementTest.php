<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Support\Facades\Hash;

test('an authenticated user can change their password', function (): void {
    $user = User::factory()->create(['password' => Hash::make('current-Passw0rd!')]);

    $response = $this->actingAs($user)->put(route('password.update'), [
        'current_password' => 'current-Passw0rd!',
        'password' => 'Br4nd!NewPassw0rd',
        'password_confirmation' => 'Br4nd!NewPassw0rd',
    ]);

    $response->assertSessionHasNoErrors();
    expect(Hash::check('Br4nd!NewPassw0rd', $user->fresh()->password))->toBeTrue();
});

test('changing the password is rejected when the current password is wrong', function (): void {
    $user = User::factory()->create(['password' => Hash::make('current-Passw0rd!')]);

    $response = $this->actingAs($user)->put(route('password.update'), [
        'current_password' => 'not-the-current-password',
        'password' => 'Br4nd!NewPassw0rd',
        'password_confirmation' => 'Br4nd!NewPassw0rd',
    ]);

    $response->assertSessionHasErrors('current_password');
});

test('a password cannot be reused from recent history', function (): void {
    config(['security.password_history_count' => 3]);

    $user = User::factory()->create(['password' => Hash::make('current-Passw0rd!')]);
    $user->passwordHistories()->create(['password' => $user->password]);

    $response = $this->actingAs($user)->put(route('password.update'), [
        'current_password' => 'current-Passw0rd!',
        'password' => 'current-Passw0rd!',
        'password_confirmation' => 'current-Passw0rd!',
    ]);

    $response->assertSessionHasErrors('password');
});

test('an expired password forces a redirect to the change-password screen', function (): void {
    config(['security.password_expiry_days' => 90]);

    $user = User::factory()->create(['password_changed_at' => now()->subDays(100)]);

    $response = $this->actingAs($user)->get(route('dashboard'));

    $response->assertRedirect(route('password.edit'));
});

test('a non-expired password does not redirect', function (): void {
    config(['security.password_expiry_days' => 90]);

    $user = User::factory()->create(['password_changed_at' => now()->subDays(10)]);

    $response = $this->actingAs($user)->get(route('dashboard'));

    $response->assertOk();
});
