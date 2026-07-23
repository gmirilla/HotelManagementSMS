<?php

declare(strict_types=1);

namespace App\Domain\Guest\Enums;

enum GuestContactRelationType: string
{
    case Family = 'family';
    case Emergency = 'emergency';
    case Companion = 'companion';
}
