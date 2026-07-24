<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\EventBooking;
use App\Models\User;

class EventBookingPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasAnyPermission(['events.manage', 'events.view']);
    }

    public function view(User $user, EventBooking $booking): bool
    {
        return $user->canAccessBranch($booking->branch_id) && $user->hasAnyPermission(['events.manage', 'events.view']);
    }

    public function create(User $user): bool
    {
        return $user->hasPermissionTo('events.manage');
    }

    public function update(User $user, EventBooking $booking): bool
    {
        return $user->canAccessBranch($booking->branch_id) && $user->hasPermissionTo('events.manage');
    }
}
