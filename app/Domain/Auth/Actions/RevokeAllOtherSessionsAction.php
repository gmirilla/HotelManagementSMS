<?php

declare(strict_types=1);

namespace App\Domain\Auth\Actions;

use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * "Log out everywhere" (FR-AUTH-009): revokes every session for the user
 * except the one making this request.
 */
class RevokeAllOtherSessionsAction
{
    public function handle(User $user, string $currentSessionId): void
    {
        DB::table('sessions')
            ->where('user_id', $user->id)
            ->where('id', '!=', $currentSessionId)
            ->delete();
    }
}
