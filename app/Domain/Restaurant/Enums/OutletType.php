<?php

declare(strict_types=1);

namespace App\Domain\Restaurant\Enums;

enum OutletType: string
{
    case Restaurant = 'restaurant';
    case Bar = 'bar';
    case RoomService = 'room_service';
    case Banquet = 'banquet';
}
