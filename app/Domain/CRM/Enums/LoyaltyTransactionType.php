<?php

declare(strict_types=1);

namespace App\Domain\CRM\Enums;

enum LoyaltyTransactionType: string
{
    case Earn = 'earn';
    case Redeem = 'redeem';
    case Adjust = 'adjust';
    case Expire = 'expire';
}
