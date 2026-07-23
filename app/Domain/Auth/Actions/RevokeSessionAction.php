<?php

declare(strict_types=1);

namespace App\Domain\Auth\Actions;

use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * Revokes one of a user's active sessions (FR-AUTH-009). Relies on the
 * `database` session driver — see system-architecture.md §4 for why.
 */
class RevokeSessionAction
{
    public function handle(User $user, string $sessionId): void
    {
        DB::table('sessions')
            ->where('id', $sessionId)
            ->where('user_id', $user->id)
            ->delete();
    }
}
