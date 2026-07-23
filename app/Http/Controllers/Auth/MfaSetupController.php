<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Domain\Auth\Actions\ConfirmMfaAction;
use App\Domain\Auth\Actions\DisableMfaAction;
use App\Domain\Auth\Actions\EnableMfaAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\MfaCodeRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class MfaSetupController extends Controller
{
    public function create(Request $request, EnableMfaAction $enableMfa): View
    {
        $user = $request->user();

        if ($user->mfa_enabled) {
            return view('auth.mfa-setup', ['enabled' => true]);
        }

        $setup = $enableMfa->handle($user);

        return view('auth.mfa-setup', [
            'enabled' => false,
            'secret' => $setup['secret'],
            'otpAuthUri' => $setup['otpauth_uri'],
        ]);
    }

    public function store(MfaCodeRequest $request, ConfirmMfaAction $confirmMfa): View
    {
        $recoveryCodes = $confirmMfa->handle($request->user(), $request->string('code')->toString());

        return view('auth.mfa-recovery-codes', ['recoveryCodes' => $recoveryCodes]);
    }

    public function destroy(Request $request, DisableMfaAction $disableMfa): RedirectResponse
    {
        $disableMfa->handle($request->user());

        return back()->with('status', 'mfa-disabled');
    }
}
