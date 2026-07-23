<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Domain\Auth\Actions\AttemptLoginAction;
use App\Domain\Auth\Actions\LogoutAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class LoginController extends Controller
{
    public function create(): View
    {
        return view('auth.login');
    }

    public function store(LoginRequest $request, AttemptLoginAction $attemptLogin): RedirectResponse
    {
        $user = $attemptLogin->handle(
            $request->string('email')->toString(),
            $request->string('password')->toString(),
            $request->throttleKey(),
        );

        if ($user->requiresMfa()) {
            $request->session()->put('mfa.user.id', $user->id);
            $request->session()->put('mfa.remember', $request->boolean('remember'));

            return redirect()->route('mfa.challenge');
        }

        Auth::login($user, $request->boolean('remember'));
        $request->session()->regenerate();

        return redirect()->intended(route('dashboard'));
    }

    public function destroy(Request $request, LogoutAction $logout): RedirectResponse
    {
        $logout->handle($request);

        return redirect()->route('login');
    }
}
