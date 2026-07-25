<?php

declare(strict_types=1);

namespace App\Domain\Inventory\Actions;

use App\Domain\Inventory\Enums\StockMovementType;
use App\Domain\Inventory\Support\InventoryQuantityCalculator;
use App\Models\InventoryItem;
use App\Models\StockMovement;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class IssueStockAction
{
    public function __construct(private readonly InventoryQuantityCalculator $quantityCalculator) {}

    public function handle(
        InventoryItem $item,
        int $quantity,
        ?User $recordedBy = null,
        ?Model $reference = null,
        StockMovementType $movementType = StockMovementType::Issue,
    ): StockMovement {
        if ($quantity <= 0) {
            throw ValidationException::withMessages(['quantity' => __('Issued quantity must be greater than zero.')]);
        }

        return DB::transaction(function () use ($item, $quantity, $recordedBy, $reference, $movementType) {
            // Locked so two concurrent issues against the same item can't
            // both read a quantity_on_hand that's stale by the time either
            // commits, and both squeak past the availability check below.
            $lockedItem = InventoryItem::whereKey($item->id)->lockForUpdate()->firstOrFail();

            if ($lockedItem->quantity_on_hand < $quantity) {
                throw ValidationException::withMessages([
                    'quantity' => __('Only :available :unit of :item on hand — cannot issue :requested.', [
                        'available' => $lockedItem->quantity_on_hand,
                        'unit' => $lockedItem->unit_of_measure,
                        'item' => $lockedItem->name,
                        'requested' => $quantity,
                    ]),
                ]);
            }

            $movement = $lockedItem->stockMovements()->create([
                'movement_type' => $movementType,
                'quantity' => -$quantity,
                'unit_cost_cents' => $lockedItem->average_cost_cents,
                'reference_type' => $reference?->getMorphClass(),
                'reference_id' => $reference?->getKey(),
                'recorded_by_user_id' => $recordedBy?->id,
            ]);

            $this->quantityCalculator->recalculate($lockedItem);

            return $movement;
        });
    }
}
