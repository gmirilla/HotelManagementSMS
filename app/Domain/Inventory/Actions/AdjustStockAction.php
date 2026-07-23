<?php

declare(strict_types=1);

namespace App\Domain\Inventory\Actions;

use App\Domain\Inventory\Enums\StockMovementType;
use App\Domain\Inventory\Support\InventoryQuantityCalculator;
use App\Models\InventoryItem;
use App\Models\StockMovement;
use App\Models\User;
use Illuminate\Validation\ValidationException;

/**
 * A manual correction (e.g. after a physical stocktake) — the only movement
 * type whose quantity may be either sign, since it's reconciling reality
 * against the ledger rather than recording a specific in/out event.
 */
class AdjustStockAction
{
    public function __construct(private readonly InventoryQuantityCalculator $quantityCalculator) {}

    public function handle(InventoryItem $item, int $signedQuantity, User $recordedBy, string $reason = ''): StockMovement
    {
        if ($signedQuantity === 0) {
            throw ValidationException::withMessages(['quantity' => __('Adjustment quantity cannot be zero.')]);
        }

        $movement = $item->stockMovements()->create([
            'movement_type' => StockMovementType::Adjustment,
            'quantity' => $signedQuantity,
            'unit_cost_cents' => $item->average_cost_cents,
            'notes' => $reason ?: null,
            'recorded_by_user_id' => $recordedBy->id,
        ]);

        $this->quantityCalculator->recalculate($item);

        return $movement;
    }
}
