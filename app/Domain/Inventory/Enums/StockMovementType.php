<?php

declare(strict_types=1);

namespace App\Domain\Inventory\Enums;

enum StockMovementType: string
{
    case Receipt = 'receipt';
    case Issue = 'issue';
    case Transfer = 'transfer';
    case Adjustment = 'adjustment';
    case Wastage = 'wastage';

    /**
     * Whether this movement type increases (true) or decreases (false)
     * quantity on hand. Adjustments can go either way and are handled
     * separately by the caller supplying a signed quantity.
     */
    public function isInbound(): bool
    {
        return $this === self::Receipt;
    }
}
