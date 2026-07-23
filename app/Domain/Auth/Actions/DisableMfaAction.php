<?php

declare(strict_types=1);

namespace App\Domain\Auth\Actions;

use App\Models\User;

class DisableMfaAction
{
    public function handle(User $user): void
    {
        $user->forceFill([
            'mfa_enabled' => false,
            'mfa_secret' => null,
            'mfa_recovery_codes' => null,
        ])->save();
    }
}
