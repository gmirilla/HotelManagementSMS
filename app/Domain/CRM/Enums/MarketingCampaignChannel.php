<?php

declare(strict_types=1);

namespace App\Domain\CRM\Enums;

enum MarketingCampaignChannel: string
{
    case Email = 'email';
    case Sms = 'sms';
}
