<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;

test('the forgot password screen renders', function (): void {
    $this->get(route('password.request'))->assertOk();
});

test('a reset link is requested and can be used to set a new password', function (): void {
    Notification::fake();

    $user = User::factory()->create(['password' => Hash::make('old-Passw0rd!')]);

    $this->post(route('password.email'), ['email' => $user->email])
        ->assertSessionHas('status');

    Notification::assertSentTo($user, ResetPassword::class, function (ResetPassword $notification) use ($user) {
        $token = $notification->token;

        $response = $this->post(route('password.store'), [
            'token' => $token,
            'email' => $user->email,
            'password' => 'Br4nd!NewPassw0rd',
            'password_confirmation' => 'Br4nd!NewPassw0rd',
        ]);

        $response->assertRedirect(route('login'));

        expect(Hash::check('Br4nd!NewPassw0rd', $user->fresh()->password))->toBeTrue();

        return true;
    });
});

test('requesting a reset link does not reveal whether the email is registered', function (): void {
    Notification::fake();

    $response = $this->post(route('password.email'), ['email' => 'nobody@example.com']);

    $response->assertSessionHas('status');
    $response->assertSessionDoesntHaveErrors();
});
