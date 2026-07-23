<?php

declare(strict_types=1);

namespace App\Domain\Housekeeping\Enums;

enum HousekeepingTaskType: string
{
    case CheckoutClean = 'checkout_clean';
    case StayoverClean = 'stayover_clean';
    case DeepClean = 'deep_clean';
    case Inspection = 'inspection';
}
