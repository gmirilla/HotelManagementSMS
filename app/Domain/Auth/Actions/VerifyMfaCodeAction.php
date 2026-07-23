<?php

declare(strict_types=1);

namespace App\Domain\Auth\Actions;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use PragmaRX\Google2FA\Google2FA;

/**
 * Verifies a login-time MFA challenge response, accepting either a live TOTP
 * code or a one-time recovery code (consumed on use).
 */
class VerifyMfaCodeAction
{
    public function __construct(private readonly Google2FA $google2fa) {}

    public function handle(User $user, string $code): bool
    {
        if ($user->mfa_secret && $this->google2fa->verifyKey($user->mfa_secret, $code)) {
            return true;
        }

        return $this->tryConsumeRecoveryCode($user, $code);
    }

    private function tryConsumeRecoveryCode(User $user, string $code): bool
    {
        $recoveryCodes = $user->mfa_recovery_codes ?? [];

        foreach ($recoveryCodes as $index => $hashedCode) {
            if (Hash::check($code, $hashedCode)) {
                unset($recoveryCodes[$index]);
                $user->forceFill(['mfa_recovery_codes' => array_values($recoveryCodes)])->save();

                return true;
            }
        }

        return false;
    }
}
