<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\DisciplinaryRecord;
use App\Models\User;

/**
 * FR-HR-005: disciplinary records have restricted visibility — only HR
 * management and the employee themselves may view a record.
 */
class DisciplinaryRecordPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('hr.manage') || $user->employee !== null;
    }

    public function view(User $user, DisciplinaryRecord $record): bool
    {
        return ($user->canAccessBranch($record->employee->branch_id) && $user->hasPermissionTo('hr.manage'))
            || $user->id === $record->employee->user_id;
    }

    public function create(User $user): bool
    {
        return $user->hasPermissionTo('hr.manage');
    }
}
