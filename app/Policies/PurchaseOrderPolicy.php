<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\PurchaseOrder;
use App\Models\User;

class PurchaseOrderPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasAnyPermission(['procurement.manage', 'inventory.manage']);
    }

    public function view(User $user, PurchaseOrder $purchaseOrder): bool
    {
        return $user->canAccessBranch($purchaseOrder->branch_id) && $user->hasAnyPermission(['procurement.manage', 'inventory.manage']);
    }

    public function create(User $user): bool
    {
        return $user->hasPermissionTo('procurement.manage');
    }

    public function receive(User $user, PurchaseOrder $purchaseOrder): bool
    {
        return $user->canAccessBranch($purchaseOrder->branch_id) && $user->hasAnyPermission(['procurement.manage', 'inventory.manage']);
    }
}
