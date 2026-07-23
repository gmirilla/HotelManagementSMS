<?php

declare(strict_types=1);

namespace App\Domain\Maintenance\Actions;

use App\Domain\Maintenance\Enums\WorkOrderStatus;
use App\Domain\Room\Enums\HousekeepingStatus;
use App\Domain\Room\Enums\RoomStatus;
use App\Models\MaintenanceWorkOrder;
use App\Models\User;
use Illuminate\Validation\ValidationException;

class VerifyMaintenanceWorkOrderAction
{
    public function handle(MaintenanceWorkOrder $workOrder, User $verifier): MaintenanceWorkOrder
    {
        if ($workOrder->status !== WorkOrderStatus::Completed) {
            throw ValidationException::withMessages(['status' => __('Only a completed work order can be verified.')]);
        }

        $workOrder->update([
            'status' => WorkOrderStatus::Verified,
            'verified_at' => now(),
        ]);

        $room = $workOrder->room;

        if ($room && $room->status === RoomStatus::OutOfOrder) {
            $stillBlocked = $room->maintenanceWorkOrders()
                ->whereIn('status', ['open', 'in_progress'])
                ->exists();

            if (! $stillBlocked) {
                $room->update([
                    'status' => RoomStatus::VacantDirty,
                    'housekeeping_status' => HousekeepingStatus::Dirty,
                ]);
                $room->statusLogs()->create([
                    'from_status' => RoomStatus::OutOfOrder->value,
                    'to_status' => RoomStatus::VacantDirty->value,
                    'changed_by_user_id' => $verifier->id,
                    'reason' => "Maintenance work order #{$workOrder->id} verified",
                ]);
            }
        }

        return $workOrder;
    }
}
