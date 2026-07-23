<?php

declare(strict_types=1);

namespace App\Domain\Restaurant\Enums;

enum OrderType: string
{
    case DineIn = 'dine_in';
    case RoomService = 'room_service';
    case Takeaway = 'takeaway';
}
