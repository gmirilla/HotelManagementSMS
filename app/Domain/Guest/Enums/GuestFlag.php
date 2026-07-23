<?php

declare(strict_types=1);

namespace App\Domain\Guest\Enums;

enum GuestFlag: string
{
    case None = 'none';
    case Vip = 'vip';
    case Blacklisted = 'blacklisted';
}
