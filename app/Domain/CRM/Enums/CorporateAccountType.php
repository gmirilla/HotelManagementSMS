<?php

declare(strict_types=1);

namespace App\Domain\CRM\Enums;

enum CorporateAccountType: string
{
    case Corporate = 'corporate';
    case TravelAgent = 'travel_agent';
}
