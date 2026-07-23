<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\MaintenanceWorkOrder;
use App\Models\User;

class MaintenanceWorkOrderPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasAnyPermission(['maintenance.manage', 'maintenance.create', 'rooms.view']);
    }

    public function view(User $user, MaintenanceWorkOrder $workOrder): bool
    {
        return $user->canAccessBranch($workOrder->branch_id)
            && $user->hasAnyPermission(['maintenance.manage', 'maintenance.create']);
    }

    public function create(User $user): bool
    {
        return $user->hasAnyPermission(['maintenance.manage', 'maintenance.create']);
    }

    public function update(User $user, MaintenanceWorkOrder $workOrder): bool
    {
        if (! $user->canAccessBranch($workOrder->branch_id)) {
            return false;
        }

        return $user->hasPermissionTo('maintenance.manage')
            || ($workOrder->assigned_to_user_id === $user->id);
    }

    public function verify(User $user, MaintenanceWorkOrder $workOrder): bool
    {
        return $user->canAccessBranch($workOrder->branch_id) && $user->hasPermissionTo('maintenance.manage');
    }
}
