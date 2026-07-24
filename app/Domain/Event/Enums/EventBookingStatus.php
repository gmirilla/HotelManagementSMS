<?php

declare(strict_types=1);

namespace App\Domain\Event\Enums;

enum EventBookingStatus: string
{
    case Tentative = 'tentative';
    case Confirmed = 'confirmed';
    case Completed = 'completed';
    case Cancelled = 'cancelled';
}
