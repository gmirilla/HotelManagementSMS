<?php

declare(strict_types=1);

namespace App\Domain\Housekeeping\Enums;

enum LostFoundStatus: string
{
    case Held = 'held';
    case Returned = 'returned';
    case Disposed = 'disposed';
}
