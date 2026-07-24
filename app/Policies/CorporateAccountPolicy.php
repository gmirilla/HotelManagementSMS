<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\CorporateAccount;
use App\Models\User;

class CorporateAccountPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasAnyPermission(['crm.manage', 'crm.view']);
    }

    public function view(User $user, CorporateAccount $corporateAccount): bool
    {
        return $user->hasAnyPermission(['crm.manage', 'crm.view']);
    }

    public function create(User $user): bool
    {
        return $user->hasPermissionTo('crm.manage');
    }

    public function update(User $user, CorporateAccount $corporateAccount): bool
    {
        return $user->hasPermissionTo('crm.manage');
    }
}
