<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Branch;
use App\Models\Room;
use App\Models\User;

class RoomPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasAnyPermission(['rooms.manage', 'rooms.view']);
    }

    public function view(User $user, Room $room): bool
    {
        return $user->canAccessBranch($room->branch_id) && $user->hasAnyPermission(['rooms.manage', 'rooms.view']);
    }

    public function create(User $user, Branch|int $branch): bool
    {
        $branchId = $branch instanceof Branch ? $branch->id : $branch;

        return $user->canAccessBranch($branchId) && $user->hasPermissionTo('rooms.manage');
    }

    public function update(User $user, Room $room): bool
    {
        return $user->canAccessBranch($room->branch_id) && $user->hasPermissionTo('rooms.manage');
    }

    public function delete(User $user, Room $room): bool
    {
        return $user->canAccessBranch($room->branch_id) && $user->hasPermissionTo('rooms.manage');
    }
}
