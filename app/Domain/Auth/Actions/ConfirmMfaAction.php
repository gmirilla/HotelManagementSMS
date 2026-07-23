<?php

declare(strict_types=1);

namespace App\Domain\Auth\Actions;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use PragmaRX\Google2FA\Google2FA;

/**
 * Finishes MFA setup: proves the user's authenticator app has the correct
 * secret before MFA is actually enforced, then issues one-time recovery
 * codes (shown once — only their hashes are persisted).
 */
class ConfirmMfaAction
{
    public function __construct(private readonly Google2FA $google2fa) {}

    /**
     * @return list<string> plaintext recovery codes, to be displayed once
     */
    public function handle(User $user, string $code): array
    {
        if (! $user->mfa_secret || ! $this->google2fa->verifyKey($user->mfa_secret, $code)) {
            throw ValidationException::withMessages([
                'code' => __('The provided authentication code is invalid.'),
            ]);
        }

        $plainRecoveryCodes = collect(range(1, (int) config('security.mfa_recovery_codes_count')))
            ->map(fn () => Str::random(4) . '-' . Str::random(4) . '-' . Str::random(4))
            ->values()
            ->all();

        $user->forceFill([
            'mfa_enabled' => true,
            'mfa_recovery_codes' => array_map(fn (string $plain) => Hash::make($plain), $plainRecoveryCodes),
        ])->save();

        return $plainRecoveryCodes;
    }
}
