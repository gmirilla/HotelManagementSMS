<?php

declare(strict_types=1);

namespace App\Domain\CRM\Enums;

enum FeedbackStatus: string
{
    case Open = 'open';
    case InProgress = 'in_progress';
    case Resolved = 'resolved';
    case Closed = 'closed';
}
