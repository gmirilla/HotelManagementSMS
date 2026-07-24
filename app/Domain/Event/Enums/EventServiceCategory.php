<?php

declare(strict_types=1);

namespace App\Domain\Event\Enums;

enum EventServiceCategory: string
{
    case Catering = 'catering';
    case Equipment = 'equipment';
    case Other = 'other';
}
