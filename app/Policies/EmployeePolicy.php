<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Employee;
use App\Models\User;

class EmployeePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('hr.manage');
    }

    public function view(User $user, Employee $employee): bool
    {
        return ($user->canAccessBranch($employee->branch_id) && $user->hasPermissionTo('hr.manage')) || $user->id === $employee->user_id;
    }

    public function create(User $user): bool
    {
        return $user->hasPermissionTo('hr.manage');
    }

    public function update(User $user, Employee $employee): bool
    {
        return $user->canAccessBranch($employee->branch_id) && $user->hasPermissionTo('hr.manage');
    }
}
