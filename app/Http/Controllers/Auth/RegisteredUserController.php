<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Domain\Auth\Actions\RegisterGuestAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\RegisterGuestRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class RegisteredUserController extends Controller
{
    public function create(): View
    {
        return view('auth.register');
    }

    public function store(RegisterGuestRequest $request, RegisterGuestAction $registerGuest): RedirectResponse
    {
        $user = $registerGuest->handle(
            $request->string('name')->toString(),
            $request->string('email')->toString(),
            $request->string('password')->toString(),
        );

        Auth::login($user);

        return redirect()->route('dashboard');
    }
}
