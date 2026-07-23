<?php

declare(strict_types=1);

namespace App\Domain\Auth\Actions;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Lang;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * Verifies credentials and applies per-account lockout (FR-AUTH-006) on top of
 * Laravel's IP-based rate limiting (NFR-SEC-004). The two are independent: an
 * attacker spraying one password across many accounts is caught by the IP
 * limiter; an attacker guessing one account's password is caught by the
 * account lockout, even from a fresh IP.
 *
 * Deliberately does NOT call Auth::login() — a user requiring MFA must not
 * receive an authenticated session until the challenge passes, so that
 * decision is left to the caller.
 */
class AttemptLoginAction
{
    public function handle(string $email, string $password, string $throttleKey): User
    {
        $this->ensureIsNotRateLimited($throttleKey);

        $user = User::where('email', $email)->first();

        if ($user?->isLocked()) {
            throw ValidationException::withMessages([
                'email' => __('This account is locked until :time due to repeated failed login attempts.', [
                    'time' => $user->locked_until->format('H:i:s'),
                ]),
            ]);
        }

        if (! $user || ! Hash::check($password, $user->password)) {
            RateLimiter::hit($throttleKey);
            $this->recordFailedAttempt($user);

            throw ValidationException::withMessages([
                'email' => __('auth.failed'),
            ]);
        }

        RateLimiter::clear($throttleKey);
        $this->recordSuccessfulAttempt($user);

        return $user;
    }

    private function ensureIsNotRateLimited(string $throttleKey): void
    {
        if (RateLimiter::tooManyAttempts($throttleKey, 10)) {
            $seconds = RateLimiter::availableIn($throttleKey);

            throw ValidationException::withMessages([
                'email' => Lang::get('auth.throttle', [
                    'seconds' => $seconds,
                    'minutes' => (int) ceil($seconds / 60),
                ]),
            ]);
        }
    }

    private function recordFailedAttempt(?User $user): void
    {
        if (! $user instanceof User) {
            return;
        }

        $user->failed_login_attempts++;

        $maxAttempts = (int) config('security.max_login_attempts');

        if ($user->failed_login_attempts >= $maxAttempts) {
            $user->locked_until = now()->addMinutes((int) config('security.lockout_minutes'));
            $user->failed_login_attempts = 0;
        }

        $user->save();
    }

    private function recordSuccessfulAttempt(User $user): void
    {
        $user->forceFill([
            'failed_login_attempts' => 0,
            'locked_until' => null,
            'remember_token' => $user->remember_token ?? Str::random(60),
        ])->save();
    }
}
