<?php

declare(strict_types=1);

namespace App\Domain\Housekeeping\Actions;

use App\Models\HousekeepingTask;
use App\Models\User;

class AssignHousekeepingTaskAction
{
    public function handle(HousekeepingTask $task, User $assignee): HousekeepingTask
    {
        $task->update(['assigned_to_user_id' => $assignee->id]);

        return $task;
    }
}
