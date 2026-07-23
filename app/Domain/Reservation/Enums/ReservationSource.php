<?php

declare(strict_types=1);

namespace App\Domain\Reservation\Enums;

enum ReservationSource: string
{
    case WalkIn = 'walk_in';
    case Phone = 'phone';
    case Online = 'online';
    case Corporate = 'corporate';
    case Group = 'group';
    case Ota = 'ota';
}
