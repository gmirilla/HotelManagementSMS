<?php

declare(strict_types=1);

namespace App\Domain\Reservation\Enums;

enum WaitlistStatus: string
{
    case Waiting = 'waiting';
    case Notified = 'notified';
    case Converted = 'converted';
    case Expired = 'expired';
}
