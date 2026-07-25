<?php

declare(strict_types=1);

namespace App\Domain\Procurement\Actions;

use App\Domain\Procurement\Enums\PurchaseOrderStatus;
use App\Models\PurchaseOrder;
use Illuminate\Validation\ValidationException;

/**
 * Cancels a PO that's no longer needed — only while nothing has been
 * delivered against it yet. Once even one line has a receipt, the supplier
 * has already fulfilled part of the order and a real ApEntry liability
 * exists for it (see ReceiveGoodsAction) — cancelling at that point would
 * silently orphan that receipt/liability, so it's refused; the remaining
 * outstanding quantity should just never be received instead.
 */
class CancelPurchaseOrderAction
{
    public function handle(PurchaseOrder $purchaseOrder): PurchaseOrder
    {
        if (! in_array($purchaseOrder->status, [PurchaseOrderStatus::Draft, PurchaseOrderStatus::Sent], true)) {
            throw ValidationException::withMessages(['status' => __('Only a draft or sent purchase order with nothing received yet can be cancelled.')]);
        }

        if ($purchaseOrder->items->contains(fn ($item) => $item->quantity_received > 0)) {
            throw ValidationException::withMessages(['status' => __('A purchase order that has already received goods cannot be cancelled.')]);
        }

        $purchaseOrder->update(['status' => PurchaseOrderStatus::Cancelled]);

        return $purchaseOrder;
    }
}
