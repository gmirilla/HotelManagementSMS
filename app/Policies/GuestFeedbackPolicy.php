<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\GuestFeedback;
use App\Models\User;

class GuestFeedbackPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasAnyPermission(['crm.manage', 'crm.view']);
    }

    public function view(User $user, GuestFeedback $feedback): bool
    {
        return $user->canAccessBranch($feedback->branch_id) && $user->hasAnyPermission(['crm.manage', 'crm.view']);
    }

    public function create(User $user): bool
    {
        return $user->hasAnyPermission(['crm.manage', 'crm.view']);
    }

    public function manage(User $user, GuestFeedback $feedback): bool
    {
        return $user->canAccessBranch($feedback->branch_id) && $user->hasPermissionTo('crm.manage');
    }
}
