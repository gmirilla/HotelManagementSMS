<?php

declare(strict_types=1);

namespace App\Domain\Room\Enums;

enum HousekeepingStatus: string
{
    case Clean = 'clean';
    case Dirty = 'dirty';
    case InProgress = 'in_progress';
    case Inspected = 'inspected';
}
