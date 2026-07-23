<?php

declare(strict_types=1);

namespace App\Domain\FrontDesk\Enums;

enum ChargeType: string
{
    case Room = 'room';
    case Tax = 'tax';
    case ServiceFee = 'service_fee';
    case Restaurant = 'restaurant';
    case LateCheckout = 'late_checkout';
    case EarlyCheckin = 'early_checkin';
    case Misc = 'misc';
    case Discount = 'discount';
}
