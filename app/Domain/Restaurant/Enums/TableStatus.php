<?php

declare(strict_types=1);

namespace App\Domain\Restaurant\Enums;

enum TableStatus: string
{
    case Free = 'free';
    case Occupied = 'occupied';
    case Reserved = 'reserved';
}
