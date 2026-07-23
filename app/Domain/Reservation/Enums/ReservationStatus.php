<?php

declare(strict_types=1);

namespace App\Domain\Reservation\Enums;

enum ReservationStatus: string
{
    case Pending = 'pending';
    case Confirmed = 'confirmed';
    case CheckedIn = 'checked_in';
    case CheckedOut = 'checked_out';
    case Cancelled = 'cancelled';
    case NoShow = 'no_show';

    public function isActive(): bool
    {
        return match ($this) {
            self::Pending, self::Confirmed, self::CheckedIn => true,
            self::CheckedOut, self::Cancelled, self::NoShow => false,
        };
    }
}
