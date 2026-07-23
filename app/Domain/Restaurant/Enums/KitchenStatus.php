<?php

declare(strict_types=1);

namespace App\Domain\Restaurant\Enums;

enum KitchenStatus: string
{
    case Queued = 'queued';
    case Preparing = 'preparing';
    case Ready = 'ready';
    case Served = 'served';
}
