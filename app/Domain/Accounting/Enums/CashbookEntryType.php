<?php

declare(strict_types=1);

namespace App\Domain\Accounting\Enums;

enum CashbookEntryType: string
{
    case CashIn = 'cash_in';
    case CashOut = 'cash_out';
}
