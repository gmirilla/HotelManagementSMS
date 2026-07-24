<?php

declare(strict_types=1);

namespace App\Domain\CRM\Support;

use App\Domain\CRM\Enums\LoyaltyTier;
use App\Domain\CRM\Enums\LoyaltyTransactionType;
use App\Models\LoyaltyAccount;

/**
 * Points balance and tier are never stored on LoyaltyAccount — both are
 * always derived from loyalty_transactions, the same ledger-truth pattern
 * used for folio/inventory/account balances elsewhere in this codebase.
 */
class LoyaltyBalanceCalculator
{
    public function pointsBalance(LoyaltyAccount $account): int
    {
        return (int) $account->transactions()->sum('points');
    }

    /**
     * Lifetime points earned (ignoring redemptions/expiry) is what drives
     * tier progression — a guest who redeems points shouldn't be demoted.
     */
    public function lifetimePointsEarned(LoyaltyAccount $account): int
    {
        return (int) $account->transactions()->where('transaction_type', LoyaltyTransactionType::Earn)->sum('points');
    }

    public function tier(LoyaltyAccount $account): LoyaltyTier
    {
        return LoyaltyTier::forLifetimePoints($this->lifetimePointsEarned($account));
    }
}
