<?php

declare(strict_types=1);

namespace App\Domain\Housekeeping\Actions;

use App\Domain\Housekeeping\Enums\HousekeepingTaskStatus;
use App\Models\HousekeepingTask;
use Illuminate\Validation\ValidationException;

/**
 * Marks the housekeeping checklist done and routes the room to inspection
 * (FR-HK-003) rather than marking it clean outright — InspectHousekeepingTaskAction
 * is the only path that actually returns a room to "Vacant Clean".
 */
class CompleteHousekeepingTaskAction
{
    /**
     * @param  array<string, bool>  $checklist
     */
    public function handle(HousekeepingTask $task, array $checklist): HousekeepingTask
    {
        if ($task->status === HousekeepingTaskStatus::Completed) {
            throw ValidationException::withMessages(['status' => __('This task is already completed.')]);
        }

        $task->update([
            'status' => HousekeepingTaskStatus::AwaitingInspection,
            'checklist' => $checklist,
            'started_at' => $task->started_at ?? now(),
            'completed_at' => now(),
        ]);

        return $task;
    }
}
