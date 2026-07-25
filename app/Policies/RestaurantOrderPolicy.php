<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\RestaurantOrder;
use App\Models\User;

class RestaurantOrderPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasAnyPermission(['restaurant.manage', 'restaurant.orders.view']);
    }

    public function view(User $user, RestaurantOrder $order): bool
    {
        return $user->canAccessBranch($order->branch_id) && $user->hasAnyPermission(['restaurant.manage', 'restaurant.orders.view']);
    }

    public function create(User $user): bool
    {
        return $user->hasAnyPermission(['restaurant.manage', 'restaurant.orders.create']);
    }

    public function update(User $user, RestaurantOrder $order): bool
    {
        return $user->canAccessBranch($order->branch_id)
            && $user->hasAnyPermission(['restaurant.manage', 'restaurant.orders.create']);
    }

    public function updateKitchenStatus(User $user, RestaurantOrder $order): bool
    {
        return $user->canAccessBranch($order->branch_id)
            && $user->hasAnyPermission(['restaurant.manage', 'restaurant.orders.update_kitchen_status']);
    }

    public function close(User $user, RestaurantOrder $order): bool
    {
        return $user->canAccessBranch($order->branch_id) && $user->hasAnyPermission(['restaurant.manage', 'restaurant.orders.create']);
    }

    public function void(User $user, RestaurantOrder $order): bool
    {
        return $user->canAccessBranch($order->branch_id) && $user->hasAnyPermission(['restaurant.manage', 'restaurant.orders.create']);
    }
}
