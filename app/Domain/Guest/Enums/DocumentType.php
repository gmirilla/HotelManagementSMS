<?php

declare(strict_types=1);

namespace App\Domain\Guest\Enums;

enum DocumentType: string
{
    case Passport = 'passport';
    case NationalId = 'national_id';
    case Visa = 'visa';
    case Other = 'other';
}
