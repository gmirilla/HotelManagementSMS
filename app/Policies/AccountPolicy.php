<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Account;
use App\Models\User;

class AccountPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasAnyPermission(['accounting.manage', 'accounting.view']);
    }

    public function view(User $user, Account $account): bool
    {
        return $user->canAccessBranch($account->branch_id) && $user->hasAnyPermission(['accounting.manage', 'accounting.view']);
    }

    public function create(User $user): bool
    {
        return $user->hasPermissionTo('accounting.manage');
    }

    public function update(User $user, Account $account): bool
    {
        return $user->canAccessBranch($account->branch_id) && $user->hasPermissionTo('accounting.manage');
    }
}
