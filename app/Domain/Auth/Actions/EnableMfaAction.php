<?php

declare(strict_types=1);

namespace App\Domain\Auth\Actions;

use App\Models\User;
use PragmaRX\Google2FA\Google2FA;

/**
 * Starts MFA setup (FR-AUTH-004): generates and persists a TOTP secret, but
 * does not enable MFA yet — ConfirmMfaAction does that once the user proves
 * they've correctly added the secret to an authenticator app.
 */
class EnableMfaAction
{
    public function __construct(private readonly Google2FA $google2fa) {}

    /**
     * @return array{secret: string, otpauth_uri: string}
     */
    public function handle(User $user): array
    {
        $secret = $this->google2fa->generateSecretKey();

        $user->forceFill(['mfa_secret' => $secret])->save();

        $otpAuthUri = $this->google2fa->getQRCodeUrl(
            config('app.name'),
            $user->email,
            $secret,
        );

        return [
            'secret' => $secret,
            'otpauth_uri' => $otpAuthUri,
        ];
    }
}
