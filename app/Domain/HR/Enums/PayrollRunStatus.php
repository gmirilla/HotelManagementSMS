<?php

declare(strict_types=1);

namespace App\Domain\HR\Enums;

enum PayrollRunStatus: string
{
    case Draft = 'draft';
    case Processed = 'processed';
    case Paid = 'paid';
}
