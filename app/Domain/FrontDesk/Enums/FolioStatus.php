<?php

declare(strict_types=1);

namespace App\Domain\FrontDesk\Enums;

enum FolioStatus: string
{
    case Open = 'open';
    case Closed = 'closed';
    case Voided = 'voided';
}
