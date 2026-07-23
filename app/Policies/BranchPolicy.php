<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Branch;
use App\Models\User;

class BranchPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Branch $branch): bool
    {
        return $user->canAccessBranch($branch->id);
    }

    public function create(User $user): bool
    {
        return $user->hasPermissionTo('branches.manage');
    }

    public function update(User $user, Branch $branch): bool
    {
        return $user->hasPermissionTo('branches.manage') && $user->tenant_id === $branch->tenant_id;
    }

    public function delete(User $user, Branch $branch): bool
    {
        return $user->hasPermissionTo('branches.manage') && $user->tenant_id === $branch->tenant_id;
    }
}
