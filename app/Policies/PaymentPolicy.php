<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Payment;
use App\Models\User;

class PaymentPolicy
{
    /**
     * Refunding is always paired with branch scoping, mirroring
     * StorePaymentRequest::authorize()'s existing canAccessBranch +
     * hasPermissionTo pattern rather than a bare permission check.
     */
    public function refund(User $user, Payment $payment): bool
    {
        return $user->canAccessBranch($payment->branch_id) && $user->hasPermissionTo('payments.refund');
    }
}
