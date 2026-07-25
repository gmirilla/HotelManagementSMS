<?php

declare(strict_types=1);

namespace App\Domain\Restaurant\Support;

use App\Domain\Accounting\Enums\TaxAppliesTo;
use App\Models\RestaurantOrder;
use App\Models\TaxRule;

/**
 * Recomputes an order's tax/total from its line items whenever they change,
 * rather than tracking running totals incrementally.
 *
 * The tax rate is looked up from the branch's own chart-of-accounts TaxRule
 * (Chart of Accounts > Tax Rules), not a hardcoded constant — a restaurant-
 * specific rule wins if the branch has configured one, otherwise a
 * branch-wide "all" rule applies, and a branch with neither configured is
 * taxed at 0% rather than guessing a rate that might not match the
 * jurisdiction (unlike a missing ledger account, an unconfigured tax rate
 * isn't necessarily a mistake — some branches genuinely charge no
 * restaurant tax).
 */
class OrderTotalCalculator
{
    public function recalculate(RestaurantOrder $order): void
    {
        $subtotalCents = $order->items->sum(fn ($item) => $item->lineTotalCents());
        $taxRatePercent = $this->taxRatePercentFor($order->branch_id);
        $taxCents = (int) round(($subtotalCents - $order->discount_cents) * $taxRatePercent / 100);

        $order->update([
            'tax_cents' => max(0, $taxCents),
            'total_cents' => max(0, $subtotalCents - $order->discount_cents) + max(0, $taxCents),
        ]);
    }

    private function taxRatePercentFor(int $branchId): float
    {
        $rule = TaxRule::where('branch_id', $branchId)
            ->where('is_active', true)
            ->where('applies_to', TaxAppliesTo::Restaurant)
            ->first()
            ?? TaxRule::where('branch_id', $branchId)
                ->where('is_active', true)
                ->where('applies_to', TaxAppliesTo::All)
                ->first();

        return $rule ? (float) $rule->rate_percent : 0.0;
    }
}
