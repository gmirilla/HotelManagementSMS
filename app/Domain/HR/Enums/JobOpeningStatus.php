<?php

declare(strict_types=1);

namespace App\Domain\HR\Enums;

enum JobOpeningStatus: string
{
    case Open = 'open';
    case OnHold = 'on_hold';
    case Closed = 'closed';
}
