<?php

declare(strict_types=1);

namespace App\Domain\Maintenance\Actions;

use App\Domain\Maintenance\Enums\WorkOrderPriority;
use App\Domain\Maintenance\Enums\WorkOrderStatus;
use App\Domain\Room\Enums\RoomStatus;
use App\Models\MaintenanceWorkOrder;
use App\Models\Room;
use App\Models\User;

class CreateMaintenanceWorkOrderAction
{
    public function handle(
        int $branchId,
        User $reportedBy,
        string $description,
        WorkOrderPriority $priority,
        ?Room $room = null,
        ?int $assetId = null,
        bool $takeRoomOutOfOrder = false,
    ): MaintenanceWorkOrder {
        $workOrder = MaintenanceWorkOrder::create([
            'branch_id' => $branchId,
            'room_id' => $room?->id,
            'asset_id' => $assetId,
            'reported_by_user_id' => $reportedBy->id,
            'priority' => $priority,
            'status' => WorkOrderStatus::Open,
            'description' => $description,
        ]);

        if ($room && $takeRoomOutOfOrder) {
            $fromStatus = $room->status;
            $room->update(['status' => RoomStatus::OutOfOrder]);
            $room->statusLogs()->create([
                'from_status' => $fromStatus->value,
                'to_status' => RoomStatus::OutOfOrder->value,
                'changed_by_user_id' => $reportedBy->id,
                'reason' => "Maintenance work order #{$workOrder->id}",
            ]);
        }

        return $workOrder;
    }
}
