<?php

declare(strict_types=1);

namespace App\Domain\CRM\Enums;

enum FeedbackType: string
{
    case Compliment = 'compliment';
    case Complaint = 'complaint';
    case Suggestion = 'suggestion';
}
