<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\PerformanceReview;
use App\Models\User;

/**
 * FR-HR-005: performance reviews have restricted visibility — only HR
 * management and the reviewed employee themselves may view a record.
 */
class PerformanceReviewPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('hr.manage') || $user->employee !== null;
    }

    public function view(User $user, PerformanceReview $review): bool
    {
        return $user->hasPermissionTo('hr.manage') || $user->id === $review->employee->user_id;
    }

    public function create(User $user): bool
    {
        return $user->hasPermissionTo('hr.manage');
    }
}
