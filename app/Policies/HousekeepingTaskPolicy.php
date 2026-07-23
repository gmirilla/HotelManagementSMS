<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\HousekeepingTask;
use App\Models\User;

class HousekeepingTaskPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasAnyPermission(['housekeeping.manage', 'housekeeping.view']);
    }

    public function view(User $user, HousekeepingTask $task): bool
    {
        return $user->canAccessBranch($task->branch_id) && $user->hasAnyPermission(['housekeeping.manage', 'housekeeping.view']);
    }

    public function create(User $user): bool
    {
        return $user->hasPermissionTo('housekeeping.manage');
    }

    public function update(User $user, HousekeepingTask $task): bool
    {
        if (! $user->canAccessBranch($task->branch_id)) {
            return false;
        }

        if ($user->hasPermissionTo('housekeeping.manage')) {
            return true;
        }

        // Housekeeping staff may only update their own assigned tasks.
        return $user->hasPermissionTo('housekeeping.update') && $task->assigned_to_user_id === $user->id;
    }

    public function inspect(User $user, HousekeepingTask $task): bool
    {
        return $user->canAccessBranch($task->branch_id) && $user->hasPermissionTo('housekeeping.manage');
    }
}
