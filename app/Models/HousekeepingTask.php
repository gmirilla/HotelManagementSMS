<?php

declare(strict_types=1);

namespace App\Models;

use App\Domain\Housekeeping\Enums\HousekeepingTaskStatus;
use App\Domain\Housekeeping\Enums\HousekeepingTaskType;
use Database\Factories\HousekeepingTaskFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Override;

#[Fillable([
    'branch_id', 'room_id', 'assigned_to_user_id', 'inspected_by_user_id', 'task_type',
    'status', 'scheduled_date', 'started_at', 'completed_at', 'checklist',
])]
class HousekeepingTask extends Model
{
    /** @use HasFactory<HousekeepingTaskFactory> */
    use HasFactory;

    #[Override]
    protected function casts(): array
    {
        return [
            'task_type' => HousekeepingTaskType::class,
            'status' => HousekeepingTaskStatus::class,
            'scheduled_date' => 'date',
            'started_at' => 'datetime',
            'completed_at' => 'datetime',
            'checklist' => 'array',
        ];
    }

    /**
     * @return BelongsTo<Branch, $this>
     */
    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    /**
     * @return BelongsTo<Room, $this>
     */
    public function room(): BelongsTo
    {
        return $this->belongsTo(Room::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function assignedTo(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to_user_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function inspectedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'inspected_by_user_id');
    }
}
