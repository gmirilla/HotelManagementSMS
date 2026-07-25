<?php

declare(strict_types=1);

namespace App\Domain\Procurement\Actions;

use App\Domain\Procurement\Enums\PurchaseOrderStatus;
use App\Models\PurchaseOrder;
use Illuminate\Validation\ValidationException;

/**
 * Marks a Draft PO as sent to the supplier — the point it becomes a real
 * commitment goods can be received against. Only a Draft can be sent; a PO
 * created directly as Sent (the default, see CreatePurchaseOrderAction)
 * never needs this.
 */
class SendPurchaseOrderAction
{
    public function handle(PurchaseOrder $purchaseOrder): PurchaseOrder
    {
        if ($purchaseOrder->status !== PurchaseOrderStatus::Draft) {
            throw ValidationException::withMessages(['status' => __('Only a draft purchase order can be sent.')]);
        }

        $purchaseOrder->update(['status' => PurchaseOrderStatus::Sent]);

        return $purchaseOrder;
    }
}
