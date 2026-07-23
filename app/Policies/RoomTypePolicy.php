<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\RoomType;
use App\Models\User;

class RoomTypePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasAnyPermission(['rooms.manage', 'rooms.view']);
    }

    public function view(User $user, RoomType $roomType): bool
    {
        return $user->canAccessBranch($roomType->branch_id) && $user->hasAnyPermission(['rooms.manage', 'rooms.view']);
    }

    public function create(User $user): bool
    {
        return $user->hasPermissionTo('rooms.manage');
    }

    public function update(User $user, RoomType $roomType): bool
    {
        return $user->canAccessBranch($roomType->branch_id) && $user->hasPermissionTo('rooms.manage');
    }

    public function delete(User $user, RoomType $roomType): bool
    {
        return $user->canAccessBranch($roomType->branch_id) && $user->hasPermissionTo('rooms.manage');
    }
}
