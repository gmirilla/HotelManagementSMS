<?php

declare(strict_types=1);

namespace App\Domain\Procurement\Actions;

use App\Domain\Procurement\Enums\PurchaseOrderStatus;
use App\Models\PurchaseOrder;
use Illuminate\Validation\ValidationException;

/**
 * Administratively closes a PO once no further deliveries are expected —
 * either it was fully received (Received), or it was only ever partially
 * received and the outstanding balance is being written off rather than
 * chased (e.g. the supplier discontinued the remaining items). Closing
 * doesn't touch inventory or the outstanding quantity itself; it just
 * stops the PO from showing up as awaiting further receipt.
 */
class ClosePurchaseOrderAction
{
    public function handle(PurchaseOrder $purchaseOrder): PurchaseOrder
    {
        if (! in_array($purchaseOrder->status, [PurchaseOrderStatus::PartiallyReceived, PurchaseOrderStatus::Received], true)) {
            throw ValidationException::withMessages(['status' => __('Only a partially or fully received purchase order can be closed.')]);
        }

        $purchaseOrder->update(['status' => PurchaseOrderStatus::Closed]);

        return $purchaseOrder;
    }
}
