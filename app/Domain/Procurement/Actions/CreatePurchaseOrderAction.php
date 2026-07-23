<?php

declare(strict_types=1);

namespace App\Domain\Procurement\Actions;

use App\Domain\Procurement\Enums\PurchaseOrderStatus;
use App\Models\PurchaseOrder;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class CreatePurchaseOrderAction
{
    /**
     * @param  list<array{inventory_item_id: int, quantity: int, unit_cost_cents: int}>  $lines
     */
    public function handle(int $branchId, int $supplierId, User $createdBy, array $lines): PurchaseOrder
    {
        if ($lines === []) {
            throw ValidationException::withMessages(['lines' => __('A purchase order needs at least one line item.')]);
        }

        return DB::transaction(function () use ($branchId, $supplierId, $createdBy, $lines) {
            $purchaseOrder = PurchaseOrder::create([
                'branch_id' => $branchId,
                'supplier_id' => $supplierId,
                'created_by_user_id' => $createdBy->id,
                'po_number' => $this->generatePoNumber(),
                'status' => PurchaseOrderStatus::Sent,
            ]);

            $totalCents = 0;

            foreach ($lines as $line) {
                $purchaseOrder->items()->create([
                    'inventory_item_id' => $line['inventory_item_id'],
                    'quantity_ordered' => $line['quantity'],
                    'quantity_received' => 0,
                    'unit_cost_cents' => $line['unit_cost_cents'],
                ]);

                $totalCents += $line['quantity'] * $line['unit_cost_cents'];
            }

            $purchaseOrder->update(['total_cents' => $totalCents]);

            return $purchaseOrder;
        });
    }

    private function generatePoNumber(): string
    {
        do {
            $number = 'PO-' . Str::upper(Str::random(6));
        } while (PurchaseOrder::where('po_number', $number)->exists());

        return $number;
    }
}
