<?php

declare(strict_types=1);

namespace App\Models;

use App\Domain\Maintenance\Enums\WorkOrderPriority;
use App\Domain\Maintenance\Enums\WorkOrderStatus;
use Database\Factories\MaintenanceWorkOrderFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Override;

#[Fillable([
    'branch_id', 'room_id', 'asset_id', 'reported_by_user_id', 'assigned_to_user_id',
    'priority', 'status', 'description', 'parts_cost_cents', 'labor_cost_cents',
    'is_preventive', 'recurrence_days', 'completed_at', 'verified_at',
])]
class MaintenanceWorkOrder extends Model
{
    /** @use HasFactory<MaintenanceWorkOrderFactory> */
    use HasFactory;

    #[Override]
    protected function casts(): array
    {
        return [
            'priority' => WorkOrderPriority::class,
            'status' => WorkOrderStatus::class,
            'parts_cost_cents' => 'integer',
            'labor_cost_cents' => 'integer',
            'is_preventive' => 'boolean',
            'recurrence_days' => 'integer',
            'completed_at' => 'datetime',
            'verified_at' => 'datetime',
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
     * @return BelongsTo<Asset, $this>
     */
    public function asset(): BelongsTo
    {
        return $this->belongsTo(Asset::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function reportedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reported_by_user_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function assignedTo(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to_user_id');
    }

    public function totalCostCents(): int
    {
        return $this->parts_cost_cents + $this->labor_cost_cents;
    }
}
