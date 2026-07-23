<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\JournalEntry;
use App\Models\User;

/**
 * Journal entries have no `update`/`delete` ability by design — real
 * accounting corrects mistakes with a reversing entry, never by editing or
 * removing a posted one, so no permission is ever granted for either here.
 */
class JournalEntryPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasAnyPermission(['accounting.manage', 'accounting.view']);
    }

    public function view(User $user, JournalEntry $entry): bool
    {
        return $user->canAccessBranch($entry->branch_id) && $user->hasAnyPermission(['accounting.manage', 'accounting.view']);
    }

    public function create(User $user): bool
    {
        return $user->hasPermissionTo('accounting.manage');
    }
}
