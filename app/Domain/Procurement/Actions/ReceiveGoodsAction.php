<?php

declare(strict_types=1);

namespace App\Domain\Procurement\Actions;

use App\Domain\Inventory\Actions\ReceiveStockAction;
use App\Domain\Procurement\Enums\PurchaseOrderStatus;
use App\Models\GoodsReceipt;
use App\Models\PurchaseOrder;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Receiving goods is the one action that actually moves inventory for a
 * purchase order — creating the PO reserves nothing in stock, only a
 * GoodsReceipt (a real, physical delivery) does (FR-INV-007).
 */
class ReceiveGoodsAction
{
    public function __construct(private readonly ReceiveStockAction $receiveStock) {}

    /**
     * @param  array<int, int>  $quantitiesByItemId  purchase_order_item_id => quantity received in this delivery
     */
    public function handle(PurchaseOrder $purchaseOrder, array $quantitiesByItemId, User $receivedBy, bool $hasDiscrepancy = false, ?string $discrepancyNotes = null): GoodsReceipt
    {
        if ($quantitiesByItemId === []) {
            throw ValidationException::withMessages(['quantities' => __('Select at least one item to receive.')]);
        }

        return DB::transaction(function () use ($purchaseOrder, $quantitiesByItemId, $receivedBy, $hasDiscrepancy, $discrepancyNotes) {
            $receipt = GoodsReceipt::create([
                'purchase_order_id' => $purchaseOrder->id,
                'received_by_user_id' => $receivedBy->id,
                'received_on' => now()->toDateString(),
                'has_discrepancy' => $hasDiscrepancy,
                'discrepancy_notes' => $discrepancyNotes,
            ]);

            foreach ($quantitiesByItemId as $purchaseOrderItemId => $quantity) {
                if ($quantity <= 0) {
                    continue;
                }

                $line = $purchaseOrder->items()->findOrFail($purchaseOrderItemId);
                $line->increment('quantity_received', $quantity);

                $this->receiveStock->handle(
                    $line->inventoryItem,
                    $quantity,
                    $line->unit_cost_cents,
                    $receivedBy,
                    $receipt,
                );
            }

            $purchaseOrder->refresh();
            $allFullyReceived = $purchaseOrder->items->every(fn ($item) => $item->isFullyReceived());
            $anyReceived = $purchaseOrder->items->contains(fn ($item) => $item->quantity_received > 0);

            $purchaseOrder->update([
                'status' => match (true) {
                    $allFullyReceived => PurchaseOrderStatus::Received,
                    $anyReceived => PurchaseOrderStatus::PartiallyReceived,
                    default => $purchaseOrder->status,
                },
            ]);

            return $receipt;
        });
    }
}
