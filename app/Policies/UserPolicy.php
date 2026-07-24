<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\User;

/**
 * User accounts are tenant-scoped even for otherwise "manage everything"
 * roles: a General Manager's users.manage permission lets them administer
 * their own hotel group's staff, never another tenant's (see
 * CorporateAccountPolicy for the earlier bug this mirrors — tenant_id was
 * missed there and fixed during the Deliverable 9 security audit).
 */
class UserPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasAnyPermission(['users.manage', 'users.view']);
    }

    public function view(User $user, User $target): bool
    {
        return $user->tenant_id === $target->tenant_id && $user->hasAnyPermission(['users.manage', 'users.view']);
    }

    public function create(User $user): bool
    {
        return $user->hasPermissionTo('users.manage');
    }

    public function update(User $user, User $target): bool
    {
        return $user->tenant_id === $target->tenant_id && $user->hasPermissionTo('users.manage');
    }

    /**
     * "Delete" here means deactivate (soft delete — a soft-deleted user is
     * excluded from the default Eloquent scope the auth provider queries
     * through, so it can no longer authenticate). A user may never
     * deactivate their own account through this screen.
     */
    public function delete(User $user, User $target): bool
    {
        return $user->id !== $target->id
            && $user->tenant_id === $target->tenant_id
            && $user->hasPermissionTo('users.manage');
    }

    public function restore(User $user, User $target): bool
    {
        return $user->tenant_id === $target->tenant_id && $user->hasPermissionTo('users.manage');
    }

    /**
     * Assigning a role is a distinct, higher-stakes action than editing a
     * profile — specifically gates against privilege escalation: only a
     * Super Administrator can grant the Super Administrator or Hotel Owner
     * roles to someone else.
     */
    public function assignRole(User $user, string $roleName): bool
    {
        if (in_array($roleName, ['Super Administrator', 'Hotel Owner'], true)) {
            return $user->hasRole('Super Administrator');
        }

        return $user->hasPermissionTo('users.manage');
    }
}
