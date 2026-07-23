<?php

declare(strict_types=1);

namespace App\Domain\Housekeeping\Enums;

enum HousekeepingTaskStatus: string
{
    case Pending = 'pending';
    case InProgress = 'in_progress';
    case AwaitingInspection = 'awaiting_inspection';
    case Completed = 'completed';
    case FailedInspection = 'failed_inspection';
}
