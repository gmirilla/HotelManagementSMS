<?php

declare(strict_types=1);

namespace App\Domain\HR\Enums;

enum DisciplinarySeverity: string
{
    case VerbalWarning = 'verbal_warning';
    case WrittenWarning = 'written_warning';
    case Suspension = 'suspension';
    case Termination = 'termination';
}
