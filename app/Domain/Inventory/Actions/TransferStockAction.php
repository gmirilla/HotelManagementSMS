<?php

declare(strict_types=1);

namespace App\Domain\Inventory\Actions;

use App\Domain\Inventory\Enums\StockMovementType;
use App\Models\InventoryItem;
use App\Models\StockMovement;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Moves stock from one warehouse to another. An InventoryItem row is
 * scoped to exactly one warehouse (unique on warehouse_id + sku), so "the
 * same item in a different warehouse" is a different row — the destination
 * item is found by matching SKU within the destination warehouse, or
 * created there (starting from zero, since it's never held any stock of
 * its own yet) if this is the first transfer of that SKU into it.
 *
 * Built on IssueStockAction/ReceiveStockAction rather than writing the
 * ledger directly, so a transfer gets the same negative-stock guard and
 * row locking as any other outbound movement, and the same weighted
 * average-cost roll-forward as any other inbound one.
 */
class TransferStockAction
{
    public function __construct(
        private readonly IssueStockAction $issueStock,
        private readonly ReceiveStockAction $receiveStock,
    ) {}

    /**
     * @return array{0: StockMovement, 1: StockMovement} the outbound and inbound movements
     */
    public function handle(InventoryItem $sourceItem, Warehouse $destinationWarehouse, int $quantity, User $recordedBy): array
    {
        if ($sourceItem->warehouse_id === $destinationWarehouse->id) {
            throw ValidationException::withMessages(['warehouse' => __('Cannot transfer stock to the same warehouse.')]);
        }

        return DB::transaction(function () use ($sourceItem, $destinationWarehouse, $quantity, $recordedBy) {
            $destinationItem = InventoryItem::firstOrCreate(
                ['warehouse_id' => $destinationWarehouse->id, 'sku' => $sourceItem->sku],
                [
                    'name' => $sourceItem->name,
                    'unit_of_measure' => $sourceItem->unit_of_measure,
                    'reorder_point' => $sourceItem->reorder_point,
                    'quantity_on_hand' => 0,
                    'average_cost_cents' => 0,
                    'is_perishable' => $sourceItem->is_perishable,
                    'expires_on' => $sourceItem->expires_on,
                ],
            );

            $outboundMovement = $this->issueStock->handle($sourceItem, $quantity, $recordedBy, null, StockMovementType::Transfer);
            $inboundMovement = $this->receiveStock->handle($destinationItem, $quantity, $sourceItem->average_cost_cents, $recordedBy, null, StockMovementType::Transfer);

            return [$outboundMovement, $inboundMovement];
        });
    }
}
