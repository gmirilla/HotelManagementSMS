<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use PragmaRX\Google2FA\Google2FA;

test('a user can set up MFA and is issued recovery codes once confirmed', function (): void {
    $user = User::factory()->create();

    $this->actingAs($user)->get(route('mfa.setup'))->assertOk();

    expect($user->fresh()->mfa_secret)->not->toBeNull()
        ->and($user->fresh()->mfa_enabled)->toBeFalse();

    $google2fa = app(Google2FA::class);
    $validCode = $google2fa->getCurrentOtp($user->fresh()->mfa_secret);

    $response = $this->actingAs($user)->post(route('mfa.confirm'), ['code' => $validCode]);

    $response->assertOk();
    expect($user->fresh()->mfa_enabled)->toBeTrue()
        ->and($user->fresh()->mfa_recovery_codes)->toHaveCount(8);
});

test('confirming MFA with an invalid code fails', function (): void {
    $user = User::factory()->create(['mfa_secret' => app(Google2FA::class)->generateSecretKey()]);

    $response = $this->actingAs($user)->post(route('mfa.confirm'), ['code' => '000000']);

    $response->assertSessionHasErrors('code');
    expect($user->fresh()->mfa_enabled)->toBeFalse();
});

test('a user can disable MFA', function (): void {
    $user = User::factory()->create(['mfa_enabled' => true, 'mfa_secret' => 'SOMESECRET']);

    $this->actingAs($user)->delete(route('mfa.disable'));

    expect($user->fresh()->mfa_enabled)->toBeFalse()
        ->and($user->fresh()->mfa_secret)->toBeNull();
});

test('the MFA login challenge accepts a valid TOTP code and completes login', function (): void {
    $google2fa = app(Google2FA::class);
    $secret = $google2fa->generateSecretKey();

    $user = User::factory()->create([
        'password' => Hash::make('correct-password'),
        'mfa_enabled' => true,
        'mfa_secret' => $secret,
    ]);

    $this->post(route('login'), ['email' => $user->email, 'password' => 'correct-password']);

    $response = $this->post(route('mfa.challenge.verify'), ['code' => $google2fa->getCurrentOtp($secret)]);

    $this->assertAuthenticatedAs($user);
    $response->assertRedirect(route('dashboard'));
});

test('the MFA login challenge rejects an invalid code', function (): void {
    $user = User::factory()->create([
        'password' => Hash::make('correct-password'),
        'mfa_enabled' => true,
        'mfa_secret' => app(Google2FA::class)->generateSecretKey(),
    ]);

    $this->post(route('login'), ['email' => $user->email, 'password' => 'correct-password']);

    $response = $this->post(route('mfa.challenge.verify'), ['code' => '000000']);

    $response->assertSessionHasErrors('code');
    $this->assertGuest();
});

test('a recovery code can be used once during the MFA challenge and then is consumed', function (): void {
    $user = User::factory()->create([
        'password' => Hash::make('correct-password'),
        'mfa_enabled' => true,
        'mfa_secret' => app(Google2FA::class)->generateSecretKey(),
        'mfa_recovery_codes' => [Hash::make('RECOVERY-CODE-1')],
    ]);

    $this->post(route('login'), ['email' => $user->email, 'password' => 'correct-password']);
    $this->post(route('mfa.challenge.verify'), ['code' => 'RECOVERY-CODE-1']);

    $this->assertAuthenticatedAs($user);
    expect($user->fresh()->mfa_recovery_codes)->toBeEmpty();

    // Using the same recovery code again must fail.
    auth()->logout();
    $this->post(route('login'), ['email' => $user->email, 'password' => 'correct-password']);
    $response = $this->post(route('mfa.challenge.verify'), ['code' => 'RECOVERY-CODE-1']);

    $response->assertSessionHasErrors('code');
    $this->assertGuest();
});
