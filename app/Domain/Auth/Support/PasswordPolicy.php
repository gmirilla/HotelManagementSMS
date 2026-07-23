<?php

declare(strict_types=1);

namespace App\Domain\Auth\Support;

use Illuminate\Validation\Rules\Password;

/**
 * Central password-complexity rule (FR-AUTH-007) shared by every entry point
 * that sets a password (registration, reset, change) so the policy can only
 * be defined — and only drift — in one place.
 */
class PasswordPolicy
{
    /**
     * @return array<int, Password|string>
     */
    public static function rules(): array
    {
        $password = Password::min(10)->mixedCase()->numbers()->symbols();

        // Skip the Have I Been Pwned network lookup outside production-like
        // environments so tests stay hermetic and CI doesn't depend on
        // outbound network access.
        if (app()->environment('production', 'staging')) {
            $password = $password->uncompromised();
        }

        return ['required', 'string', 'confirmed', $password];
    }
}
