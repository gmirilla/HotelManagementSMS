<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Domain\Housekeeping\Enums\HousekeepingTaskStatus;
use App\Domain\Housekeeping\Enums\HousekeepingTaskType;
use App\Models\Branch;
use App\Models\HousekeepingTask;
use App\Models\Room;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<HousekeepingTask>
 */
class HousekeepingTaskFactory extends Factory
{
    public function definition(): array
    {
        return [
            'branch_id' => Branch::factory(),
            'room_id' => Room::factory(),
            'assigned_to_user_id' => null,
            'task_type' => HousekeepingTaskType::StayoverClean,
            'status' => HousekeepingTaskStatus::Pending,
            'scheduled_date' => now()->toDateString(),
            'checklist' => [
                'Bed made' => false,
                'Bathroom cleaned' => false,
                'Trash emptied' => false,
                'Amenities restocked' => false,
            ],
        ];
    }
}
