<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Reservation;
use App\Models\User;

class ReservationPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasAnyPermission(['reservations.manage', 'reservations.view']);
    }

    public function view(User $user, Reservation $reservation): bool
    {
        if ($user->hasRole('Guest')) {
            // Guest-role users only see reservations for a Guest profile
            // explicitly linked to their account (Guest::user_id) — set when
            // the booking flow associates a portal account with a profile,
            // never inferred from a possibly-reused email address.
            return $reservation->guest?->user_id === $user->id;
        }

        return $user->canAccessBranch($reservation->branch_id)
            && $user->hasAnyPermission(['reservations.manage', 'reservations.view']);
    }

    public function create(User $user): bool
    {
        return $user->hasAnyPermission(['reservations.manage', 'reservations.create']);
    }

    public function update(User $user, Reservation $reservation): bool
    {
        return $user->canAccessBranch($reservation->branch_id)
            && $user->hasAnyPermission(['reservations.manage', 'reservations.update']);
    }

    public function cancel(User $user, Reservation $reservation): bool
    {
        return $this->update($user, $reservation);
    }

    public function delete(User $user, Reservation $reservation): bool
    {
        return $user->canAccessBranch($reservation->branch_id) && $user->hasPermissionTo('reservations.manage');
    }
}
