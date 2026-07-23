<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Guest;
use App\Models\User;

class GuestPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasAnyPermission(['guests.manage', 'guests.view']);
    }

    public function view(User $user, Guest $guest): bool
    {
        if ($user->hasRole('Guest')) {
            return $guest->user_id === $user->id;
        }

        return $user->tenant_id === $guest->tenant_id && $user->hasAnyPermission(['guests.manage', 'guests.view']);
    }

    public function create(User $user): bool
    {
        return $user->hasAnyPermission(['guests.manage', 'guests.create']);
    }

    public function update(User $user, Guest $guest): bool
    {
        if ($user->hasRole('Guest')) {
            return $guest->user_id === $user->id;
        }

        return $user->tenant_id === $guest->tenant_id && $user->hasPermissionTo('guests.manage');
    }

    public function delete(User $user, Guest $guest): bool
    {
        return $user->tenant_id === $guest->tenant_id && $user->hasPermissionTo('guests.manage');
    }
}
