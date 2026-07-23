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

        $movement = $item->stockMovements()->create([
            'movement_type' => $movementType,
            'quantity' => -$quantity,
            'unit_cost_cents' => $item->average_cost_cents,
            'reference_type' => $reference?->getMorphClass(),
            'reference_id' => $reference?->getKey(),
            'recorded_by_user_id' => $recordedBy?->id,
        ]);

        $this->quantityCalculator->recalculate($item);

        return $movement;
    }
}
