<?php

declare(strict_types=1);

namespace App\Domain\Maintenance\Enums;

enum WorkOrderStatus: string
{
    case Open = 'open';
    case InProgress = 'in_progress';
    case Completed = 'completed';
    case Verified = 'verified';

    public function isBlocking(): bool
    {
        return match ($this) {
            self::Open, self::InProgress => true,
            self::Completed, self::Verified => false,
        };
    }
}
