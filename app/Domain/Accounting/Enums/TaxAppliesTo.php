<?php

declare(strict_types=1);

namespace App\Domain\Accounting\Enums;

enum TaxAppliesTo: string
{
    case Room = 'room';
    case Restaurant = 'restaurant';
    case Event = 'event';
    case All = 'all';
}
