<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Domain\Auth\Actions\VerifyMfaCodeAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\MfaCodeRequest;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

/**
 * The second step of login for users with MFA enabled/required — reached only
 * after AttemptLoginAction has already verified the password (see
 * LoginController), so this screen trusts session state, not raw input.
 */
class MfaChallengeController extends Controller
{
    public function create(Request $request): View|RedirectResponse
    {
        if (! $request->session()->has('mfa.user.id')) {
            return redirect()->route('login');
        }

        return view('auth.mfa-challenge');
    }

    public function store(MfaCodeRequest $request, VerifyMfaCodeAction $verifyMfaCode): RedirectResponse
    {
        $userId = $request->session()->get('mfa.user.id');

        if (! $userId) {
            return redirect()->route('login');
        }

        $user = User::findOrFail($userId);

        if (! $verifyMfaCode->handle($user, $request->string('code')->toString())) {
            throw ValidationException::withMessages([
                'code' => __('The provided authentication code is invalid.'),
            ]);
        }

        $remember = (bool) $request->session()->pull('mfa.remember', false);
        $request->session()->forget('mfa.user.id');

        Auth::login($user, $remember);
        $request->session()->regenerate();

        return redirect()->intended(route('dashboard'));
    }
}
