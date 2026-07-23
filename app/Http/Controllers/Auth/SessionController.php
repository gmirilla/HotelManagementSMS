<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Domain\Auth\Actions\RevokeAllOtherSessionsAction;
use App\Domain\Auth\Actions\RevokeSessionAction;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class SessionController extends Controller
{
    public function index(Request $request): View
    {
        $sessions = DB::table('sessions')
            ->where('user_id', $request->user()->id)
            ->orderByDesc('last_activity')
            ->get()
            ->map(fn ($session) => [
                'id' => $session->id,
                'ip_address' => $session->ip_address,
                'user_agent' => $session->user_agent,
                'last_active' => now()->createFromTimestamp($session->last_activity),
                'is_current_device' => $session->id === $request->session()->getId(),
            ]);

        return view('auth.sessions', ['sessions' => $sessions]);
    }

    public function destroy(Request $request, string $sessionId, RevokeSessionAction $revokeSession): RedirectResponse
    {
        $revokeSession->handle($request->user(), $sessionId);

        return back()->with('status', 'session-revoked');
    }

    public function destroyOthers(Request $request, RevokeAllOtherSessionsAction $revokeAllOtherSessions): RedirectResponse
    {
        $revokeAllOtherSessions->handle($request->user(), $request->session()->getId());

        return back()->with('status', 'other-sessions-revoked');
    }
}
