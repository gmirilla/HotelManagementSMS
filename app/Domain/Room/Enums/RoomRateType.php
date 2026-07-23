<?php

declare(strict_types=1);

namespace App\Domain\Room\Enums;

enum RoomRateType: string
{
    case Base = 'base';
    case Seasonal = 'seasonal';
    case Weekend = 'weekend';
    case Holiday = 'holiday';
    case Override = 'override';
}
