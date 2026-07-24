<?php

declare(strict_types=1);

namespace App\Domain\Branch\Actions;

use App\Models\Branch;

/**
 * Opens or closes a branch for business. Deliberately not a delete: a
 * branch accumulates rooms, reservations, accounting entries, and staff
 * assignments almost immediately, so removing the row is never the right
 * operation here — is_active is what every branch-scoped query and
 * switcher (InteractsWithActiveBranch::accessibleBranches()) should filter
 * on instead.
 */
class SetBranchActiveStatusAction
{
    public function handle(Branch $branch, bool $isActive): Branch
    {
        $branch->update(['is_active' => $isActive]);

        return $branch;
    }
}
