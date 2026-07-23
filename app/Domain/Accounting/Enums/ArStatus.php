<?php

declare(strict_types=1);

namespace App\Domain\Accounting\Enums;

enum ArStatus: string
{
    case Open = 'open';
    case PartiallyPaid = 'partially_paid';
    case Paid = 'paid';
    case WrittenOff = 'written_off';
}
