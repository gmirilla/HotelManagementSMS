<?php

declare(strict_types=1);

namespace App\Domain\Maintenance\Actions;

use App\Domain\Maintenance\Enums\WorkOrderStatus;
use App\Models\MaintenanceWorkOrder;
use Illuminate\Validation\ValidationException;

class CompleteMaintenanceWorkOrderAction
{
    public function handle(MaintenanceWorkOrder $workOrder, int $partsCostCents, int $laborCostCents): MaintenanceWorkOrder
    {
        if ($workOrder->status === WorkOrderStatus::Verified) {
            throw ValidationException::withMessages(['status' => __('This work order has already been verified.')]);
        }

        $workOrder->update([
            'status' => WorkOrderStatus::Completed,
            'parts_cost_cents' => $partsCostCents,
            'labor_cost_cents' => $laborCostCents,
            'completed_at' => now(),
        ]);

        return $workOrder;
    }
}
