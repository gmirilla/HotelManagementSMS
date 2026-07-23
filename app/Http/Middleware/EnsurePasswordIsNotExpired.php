<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * FR-AUTH-008: forces a password change once the account's password has aged
 * past the configured expiry window. Exempts the routes needed to actually
 * change the password (and log out) to avoid a redirect loop.
 */
class EnsurePasswordIsNotExpired
{
    private const array EXEMPT_ROUTES = ['password.edit', 'password.update', 'logout', 'verification.notice', 'verification.verify', 'verification.send'];

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user && $user->isPasswordExpired() && ! in_array($request->route()?->getName(), self::EXEMPT_ROUTES, true)) {
            return redirect()->route('password.edit')->with('status', 'password-expired');
        }

        return $next($request);
    }
}
