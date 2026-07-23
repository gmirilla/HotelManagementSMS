<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\PayrollRun;
use App\Models\User;

class PayrollRunPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('hr.manage');
    }

    public function view(User $user, PayrollRun $payrollRun): bool
    {
        return $user->canAccessBranch($payrollRun->branch_id) && $user->hasPermissionTo('hr.manage');
    }

    public function create(User $user): bool
    {
        return $user->hasPermissionTo('hr.manage');
    }
}
