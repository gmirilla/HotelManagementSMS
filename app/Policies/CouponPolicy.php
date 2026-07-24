<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Coupon;
use App\Models\User;

class CouponPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasAnyPermission(['crm.manage', 'crm.view']);
    }

    public function view(User $user, Coupon $coupon): bool
    {
        return $user->canAccessBranch($coupon->branch_id) && $user->hasAnyPermission(['crm.manage', 'crm.view']);
    }

    public function create(User $user): bool
    {
        return $user->hasPermissionTo('crm.manage');
    }

    public function update(User $user, Coupon $coupon): bool
    {
        return $user->canAccessBranch($coupon->branch_id) && $user->hasPermissionTo('crm.manage');
    }
}
