<?php

declare(strict_types=1);

namespace App\Domain\Guest\Enums;

enum GuestType: string
{
    case Individual = 'individual';
    case Corporate = 'corporate';
    case TravelAgent = 'travel_agent';
}
