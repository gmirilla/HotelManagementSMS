<?php

declare(strict_types=1);

namespace App\Domain\Room\Enums;

enum RoomStatus: string
{
    case VacantClean = 'vacant_clean';
    case VacantDirty = 'vacant_dirty';
    case Occupied = 'occupied';
    case OutOfOrder = 'out_of_order';
    case OutOfService = 'out_of_service';

    public function isBookable(): bool
    {
        return match ($this) {
            self::VacantClean, self::VacantDirty => true,
            self::Occupied, self::OutOfOrder, self::OutOfService => false,
        };
    }

    public function label(): string
    {
        return match ($this) {
            self::VacantClean => 'Vacant / Clean',
            self::VacantDirty => 'Vacant / Dirty',
            self::Occupied => 'Occupied',
            self::OutOfOrder => 'Out of Order',
            self::OutOfService => 'Out of Service',
        };
    }
}
