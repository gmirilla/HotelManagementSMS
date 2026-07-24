<?php

declare(strict_types=1);

namespace App\Domain\CRM\Enums;

enum MarketingCampaignStatus: string
{
    case Draft = 'draft';
    case Scheduled = 'scheduled';
    case Sent = 'sent';
}
