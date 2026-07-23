<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Domain\Auth\Actions\ChangePasswordAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\UpdatePasswordRequest;
use Illuminate\Http\RedirectResponse;

class PasswordController extends Controller
{
    public function update(UpdatePasswordRequest $request, ChangePasswordAction $changePassword): RedirectResponse
    {
        $changePassword->handle($request->user(), $request->string('password')->toString());

        return back()->with('status', 'password-updated');
    }
}
