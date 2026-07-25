<?php

declare(strict_types=1);

namespace App\Domain\Procurement\Actions;

use App\Domain\Accounting\Enums\ApStatus;
use App\Domain\Accounting\Support\CorporateLedgerPoster;
use App\Domain\Inventory\Actions\ReceiveStockAction;
use App\Domain\Procurement\Enums\PurchaseOrderStatus;
use App\Models\ApEntry;
use App\Models\GoodsReceipt;
use App\Models\PurchaseOrder;
use App\Models\Supplier;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Receiving goods is the one action that actually moves inventory for a
 * purchase order — creating the PO reserves nothing in stock, only a
 * GoodsReceipt (a real, physical delivery) does (FR-INV-007). It's also the
 * point a real payable to the supplier comes into existence — not when the
 * PO is placed — so this is what creates the ApEntry, one per delivery, for
 * the value actually received in it (FR-ACC-002).
 */
class ReceiveGoodsAction
{
    public function __construct(
        private readonly ReceiveStockAction $receiveStock,
        private readonly CorporateLedgerPoster $ledgerPoster,
    ) {}

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

            $receivedValueCents = 0;

            foreach ($quantitiesByItemId as $purchaseOrderItemId => $quantity) {
                if ($quantity <= 0) {
                    continue;
                }

                $line = $purchaseOrder->items()->findOrFail($purchaseOrderItemId);
                $line->increment('quantity_received', $quantity);
                $receivedValueCents += $quantity * $line->unit_cost_cents;

                $this->receiveStock->handle(
                    $line->inventoryItem,
                    $quantity,
                    $line->unit_cost_cents,
                    $receivedBy,
                    $receipt,
                );
            }

            if ($receivedValueCents > 0) {
                $apEntry = ApEntry::create([
                    'branch_id' => $purchaseOrder->branch_id,
                    'supplier_id' => $purchaseOrder->supplier_id,
                    'purchase_order_id' => $purchaseOrder->id,
                    'amount_cents' => $receivedValueCents,
                    'paid_cents' => 0,
                    'due_date' => $this->dueDateFor($purchaseOrder->supplier),
                    'status' => ApStatus::Open,
                ]);

                $this->ledgerPoster->postGoodsReceiptLiability($apEntry, $receivedBy);
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

    /**
     * Suppliers record their terms as free text ("Net 30", "Due on
     * receipt") rather than a structured field — parse the day count out of
     * it where possible, and fall back to a conservative 30-day default for
     * anything that doesn't match rather than guessing a due date is wrong
     * in either direction.
     */
    private function dueDateFor(Supplier $supplier): Carbon
    {
        if (preg_match('/net\s*(\d+)/i', (string) $supplier->payment_terms, $matches) === 1) {
            return now()->addDays((int) $matches[1]);
        }

        if (mb_stripos((string) $supplier->payment_terms, 'due on receipt') !== false) {
            return now();
        }

        return now()->addDays(30);
    }
}
