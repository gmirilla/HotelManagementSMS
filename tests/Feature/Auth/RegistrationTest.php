<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Support\Facades\Event;
use Spatie\Permission\Models\Role;

beforeEach(function (): void {
    Role::firstOrCreate(['name' => 'Guest', 'guard_name' => 'web']);
});

test('the registration screen renders', function (): void {
    $this->get(route('register'))->assertOk();
});

test('a guest can register with a valid password and is assigned the Guest role', function (): void {
    Event::fake([Registered::class]);

    $response = $this->post(route('register'), [
        'name' => 'Jamie Guest',
        'email' => 'jamie@example.com',
        'password' => 'Str0ng!Passw0rd',
        'password_confirmation' => 'Str0ng!Passw0rd',
    ]);

    $user = User::where('email', 'jamie@example.com')->firstOrFail();

    $this->assertAuthenticatedAs($user);
    expect($user->hasRole('Guest'))->toBeTrue()
        ->and($user->passwordHistories)->toHaveCount(1);

    $response->assertRedirect(route('dashboard'));
    Event::assertDispatched(Registered::class);
});

test('registration is rejected when the password fails the complexity policy', function (): void {
    $response = $this->post(route('register'), [
        'name' => 'Jamie Guest',
        'email' => 'jamie@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
    ]);

    $response->assertSessionHasErrors('password');
    $this->assertGuest();
});

test('registration is rejected for a duplicate email', function (): void {
    User::factory()->create(['email' => 'taken@example.com']);

    $response = $this->post(route('register'), [
        'name' => 'Jamie Guest',
        'email' => 'taken@example.com',
        'password' => 'Str0ng!Passw0rd',
        'password_confirmation' => 'Str0ng!Passw0rd',
    ]);

    $response->assertSessionHasErrors('email');
});
