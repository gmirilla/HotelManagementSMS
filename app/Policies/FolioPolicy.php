<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Folio;
use App\Models\User;

class FolioPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasAnyPermission(['folios.manage', 'folios.view']);
    }

    public function view(User $user, Folio $folio): bool
    {
        if ($user->hasRole('Guest')) {
            return $folio->guest?->user_id === $user->id;
        }

        return $user->canAccessBranch($folio->branch_id) && $user->hasAnyPermission(['folios.manage', 'folios.view']);
    }

    public function update(User $user, Folio $folio): bool
    {
        return $user->canAccessBranch($folio->branch_id) && $user->hasPermissionTo('folios.manage');
    }

    /**
     * Voiding a closed/settled folio is a distinct, higher-privilege action
     * from ordinary folio management (NFR-SEC / FR-AUTHZ-005: sensitive
     * actions require a distinct elevated permission).
     */
    public function void(User $user, Folio $folio): bool
    {
        return $user->canAccessBranch($folio->branch_id) && $user->hasPermissionTo('folios.void');
    }

    /**
     * Starting a gateway checkout reuses the same permission the manual
     * payment form already requires (payments.process — see
     * StorePaymentRequest::authorize() for the identical branch+permission
     * pairing this mirrors), always paired with branch scoping rather than
     * checked bare.
     */
    public function processGatewayPayment(User $user, Folio $folio): bool
    {
        return $user->canAccessBranch($folio->branch_id) && $user->hasPermissionTo('payments.process');
    }
}
