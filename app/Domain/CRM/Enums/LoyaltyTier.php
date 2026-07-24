<?php

declare(strict_types=1);

namespace App\Domain\CRM\Enums;

enum LoyaltyTier: string
{
    case Silver = 'silver';
    case Gold = 'gold';
    case Platinum = 'platinum';

    /**
     * Tier is driven purely by lifetime points earned (see
     * LoyaltyBalanceCalculator) — never stored on the account, so a
     * threshold change here retroactively re-tiers every guest.
     */
    public static function forLifetimePoints(int $lifetimePoints): self
    {
        return match (true) {
            $lifetimePoints >= 15000 => self::Platinum,
            $lifetimePoints >= 5000 => self::Gold,
            default => self::Silver,
        };
    }

    public function pointsMultiplier(): float
    {
        return match ($this) {
            self::Silver => 1.0,
            self::Gold => 1.25,
            self::Platinum => 1.5,
        };
    }
}
