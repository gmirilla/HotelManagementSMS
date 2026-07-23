<?php

declare(strict_types=1);

namespace App\Domain\Restaurant\Support;

use App\Models\RestaurantOrder;

/**
 * Recomputes an order's tax/total from its line items whenever they change,
 * rather than tracking running totals incrementally.
 *
 * The flat tax rate below is a placeholder until the Accounting module's
 * per-branch TAX_RULES table (docs/architecture/erd.md §9) is wired in —
 * restaurant orders should look up their branch's applicable rate there
 * instead of a hardcoded constant.
 */
class OrderTotalCalculator
{
    private const int TAX_RATE_PERCENT = 8;

    public function recalculate(RestaurantOrder $order): void
    {
        $subtotalCents = $order->items->sum(fn ($item) => $item->lineTotalCents());
        $taxCents = (int) round(($subtotalCents - $order->discount_cents) * self::TAX_RATE_PERCENT / 100);

        $order->update([
            'tax_cents' => max(0, $taxCents),
            'total_cents' => max(0, $subtotalCents - $order->discount_cents) + max(0, $taxCents),
        ]);
    }
}
