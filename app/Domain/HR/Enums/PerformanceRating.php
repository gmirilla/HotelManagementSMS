<?php

declare(strict_types=1);

namespace App\Domain\HR\Enums;

enum PerformanceRating: string
{
    case Poor = 'poor';
    case NeedsImprovement = 'needs_improvement';
    case MeetsExpectations = 'meets_expectations';
    case ExceedsExpectations = 'exceeds_expectations';
    case Outstanding = 'outstanding';
}
