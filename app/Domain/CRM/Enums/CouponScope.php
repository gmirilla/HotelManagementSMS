<?php

declare(strict_types=1);

namespace App\Domain\CRM\Enums;

enum CouponScope: string
{
    case Room = 'room';
    case Restaurant = 'restaurant';
    case Event = 'event';
    case All = 'all';
}
