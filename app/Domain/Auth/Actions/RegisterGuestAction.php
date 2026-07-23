<?php

declare(strict_types=1);

namespace App\Domain\Auth\Actions;

use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Support\Facades\Hash;

/**
 * Self-service registration for the public-facing "Guest" role (FR-AUTH-003).
 * Staff accounts are created by an administrator through the user-management
 * screens, not through this flow.
 */
class RegisterGuestAction
{
    public function handle(string $name, string $email, string $password): User
    {
        $user = User::create([
            'name' => $name,
            'email' => $email,
            'password' => Hash::make($password),
            'password_changed_at' => now(),
        ]);

        $user->assignRole('Guest');
        $user->passwordHistories()->create(['password' => $user->password]);

        event(new Registered($user));

        return $user;
    }
}
