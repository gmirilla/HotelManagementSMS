<?php

declare(strict_types=1);

namespace App\Domain\Accounting\Enums;

enum ApStatus: string
{
    case Open = 'open';
    case PartiallyPaid = 'partially_paid';
    case Paid = 'paid';
    case Disputed = 'disputed';
}
