<?php

declare(strict_types=1);

namespace App\Domain\Restaurant\Enums;

enum OrderStatus: string
{
    case Open = 'open';
    case SentToKitchen = 'sent_to_kitchen';
    case Served = 'served';
    case Closed = 'closed';
    case Voided = 'voided';
}
