<?php

declare(strict_types=1);

namespace App\Domain\CRM\Enums;

enum CouponDiscountType: string
{
    case Percent = 'percent';
    case Fixed = 'fixed';
}
