<?php

declare(strict_types=1);

namespace App\Domain\Auth\Actions;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

/**
 * Central password-write path used by both self-service "change password"
 * and the "forgot password" reset flow, so password-history (FR-AUTH-007)
 * and expiry-clock (FR-AUTH-008) enforcement can never be bypassed by using
 * one flow instead of the other.
 */
class ChangePasswordAction
{
    public function handle(User $user, string $newPassword): void
    {
        $this->ensureNotReused($user, $newPassword);

        $user->forceFill([
            'password' => Hash::make($newPassword),
            'password_changed_at' => now(),
        ])->save();

        $user->passwordHistories()->create(['password' => $user->password]);

        $this->pruneHistory($user);
    }

    private function ensureNotReused(User $user, string $newPassword): void
    {
        $historyCount = (int) config('security.password_history_count');

        if ($historyCount <= 0) {
            return;
        }

        $recentPasswords = $user->passwordHistories()
            ->latest('id')
            ->limit($historyCount)
            ->pluck('password');

        foreach ($recentPasswords as $previousHash) {
            if (Hash::check($newPassword, $previousHash)) {
                throw ValidationException::withMessages([
                    'password' => __('You cannot reuse one of your last :count passwords.', ['count' => $historyCount]),
                ]);
            }
        }
    }

    private function pruneHistory(User $user): void
    {
        $historyCount = (int) config('security.password_history_count');

        $idsToKeep = $user->passwordHistories()
            ->latest('id')
            ->limit($historyCount)
            ->pluck('id');

        $user->passwordHistories()->whereNotIn('id', $idsToKeep)->delete();
    }
}
