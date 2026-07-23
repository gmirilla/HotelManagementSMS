<?php

declare(strict_types=1);

namespace App\Domain\HR\Enums;

enum EmployeeStatus: string
{
    case Active = 'active';
    case OnLeave = 'on_leave';
    case Terminated = 'terminated';
}
