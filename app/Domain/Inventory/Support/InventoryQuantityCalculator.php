<?php

declare(strict_types=1);

namespace App\Domain\Inventory\Support;

use App\Models\InventoryItem;

/**
 * Quantity on hand is always recomputed from the stock_movements ledger
 * (append-only source of truth) rather than incremented in place — same
 * rationale as FolioBalanceCalculator for folios.
 */
class InventoryQuantityCalculator
{
    public function recalculate(InventoryItem $item): void
    {
        $quantity = (int) $item->stockMovements()->sum('quantity');

        $item->update(['quantity_on_hand' => $quantity]);
    }
}
