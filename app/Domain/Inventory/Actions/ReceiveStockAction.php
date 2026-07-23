<?php

declare(strict_types=1);

namespace App\Domain\Inventory\Actions;

use App\Domain\Inventory\Enums\StockMovementType;
use App\Domain\Inventory\Support\InventoryQuantityCalculator;
use App\Models\InventoryItem;
use App\Models\StockMovement;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\ValidationException;

/**
 * Records incoming stock and rolls the item's average_cost_cents forward
 * using a weighted average — the standard valuation method for FR-INV-005.
 */
class ReceiveStockAction
{
    public function __construct(private readonly InventoryQuantityCalculator $quantityCalculator) {}

    public function handle(
        InventoryItem $item,
        int $quantity,
        int $unitCostCents,
        User $recordedBy,
        ?Model $reference = null,
    ): StockMovement {
        if ($quantity <= 0) {
            throw ValidationException::withMessages(['quantity' => __('Received quantity must be greater than zero.')]);
        }

        $movement = $item->stockMovements()->create([
            'movement_type' => StockMovementType::Receipt,
            'quantity' => $quantity,
            'unit_cost_cents' => $unitCostCents,
            'reference_type' => $reference?->getMorphClass(),
            'reference_id' => $reference?->getKey(),
            'recorded_by_user_id' => $recordedBy->id,
        ]);

        $existingQuantity = $item->quantity_on_hand;
        $newAverageCost = $existingQuantity + $quantity > 0
            ? (int) round((($existingQuantity * $item->average_cost_cents) + ($quantity * $unitCostCents)) / ($existingQuantity + $quantity))
            : $unitCostCents;

        $item->update(['average_cost_cents' => $newAverageCost]);
        $this->quantityCalculator->recalculate($item);

        return $movement;
    }
}
