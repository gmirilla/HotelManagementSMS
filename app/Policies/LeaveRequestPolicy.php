<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\LeaveRequest;
use App\Models\User;

class LeaveRequestPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('hr.manage') || $user->employee !== null;
    }

    public function view(User $user, LeaveRequest $leaveRequest): bool
    {
        return ($user->canAccessBranch($leaveRequest->employee->branch_id) && $user->hasPermissionTo('hr.manage'))
            || $user->id === $leaveRequest->employee->user_id;
    }

    public function create(User $user): bool
    {
        return $user->hasPermissionTo('hr.manage') || $user->employee !== null;
    }

    /**
     * Only an HR manager can approve/reject — the requesting employee never
     * approves their own leave. Scoped to the request's branch: an HR
     * officer assigned to one branch cannot approve/reject another
     * branch's leave requests just because they hold hr.manage.
     */
    public function review(User $user, LeaveRequest $leaveRequest): bool
    {
        return $user->canAccessBranch($leaveRequest->employee->branch_id) && $user->hasPermissionTo('hr.manage');
    }
}
