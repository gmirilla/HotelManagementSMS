<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\InventoryItem;
use App\Models\User;

class InventoryItemPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasAnyPermission(['inventory.manage', 'inventory.view']);
    }

    public function view(User $user, InventoryItem $item): bool
    {
        return $user->canAccessBranch($item->warehouse->branch_id) && $user->hasAnyPermission(['inventory.manage', 'inventory.view']);
    }

    public function create(User $user): bool
    {
        return $user->hasPermissionTo('inventory.manage');
    }

    public function update(User $user, InventoryItem $item): bool
    {
        return $user->canAccessBranch($item->warehouse->branch_id) && $user->hasPermissionTo('inventory.manage');
    }
}
