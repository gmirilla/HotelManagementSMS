<?php

declare(strict_types=1);

namespace App\Domain\Maintenance\Enums;

enum WorkOrderPriority: string
{
    case Low = 'low';
    case Medium = 'medium';
    case High = 'high';
    case Urgent = 'urgent';
}
