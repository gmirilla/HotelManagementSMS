<?php

declare(strict_types=1);

namespace App\Domain\Housekeeping\Actions;

use App\Domain\Housekeeping\Enums\HousekeepingTaskStatus;
use App\Domain\Room\Enums\HousekeepingStatus;
use App\Domain\Room\Enums\RoomStatus;
use App\Models\HousekeepingTask;
use App\Models\User;
use Illuminate\Validation\ValidationException;

class InspectHousekeepingTaskAction
{
    public function handle(HousekeepingTask $task, User $inspector, bool $passed): HousekeepingTask
    {
        if ($task->status !== HousekeepingTaskStatus::AwaitingInspection) {
            throw ValidationException::withMessages(['status' => __('Only a task awaiting inspection can be inspected.')]);
        }

        $task->update([
            'status' => $passed ? HousekeepingTaskStatus::Completed : HousekeepingTaskStatus::FailedInspection,
            'inspected_by_user_id' => $inspector->id,
        ]);

        if ($passed) {
            $room = $task->room;
            $room->update([
                'housekeeping_status' => HousekeepingStatus::Clean,
                'status' => $room->status === RoomStatus::VacantDirty ? RoomStatus::VacantClean : $room->status,
            ]);
        }

        return $task;
    }
}
