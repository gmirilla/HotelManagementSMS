<?php

declare(strict_types=1);

namespace App\Domain\Reporting\Support;

use App\Models\FolioCharge;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class RevenueReportCalculator
{
    /**
     * @return array{start: string, end: string, total_cents: int, by_charge_type: Collection<int, array{charge_type: string, amount_cents: int}>}
     */
    public function forPeriod(int $branchId, Carbon $start, Carbon $end): array
    {
        $charges = FolioCharge::whereHas('folio', fn ($query) => $query->where('branch_id', $branchId))
            ->whereBetween('charge_date', [$start->toDateString(), $end->toDateString()])
            ->get();

        $byChargeType = $charges->groupBy(fn (FolioCharge $charge) => $charge->charge_type->value)
            ->map(fn (Collection $group, string $type) => [
                'charge_type' => $type,
                'amount_cents' => $group->sum('amount_cents'),
            ])
            ->values();

        return [
            'start' => $start->toDateString(),
            'end' => $end->toDateString(),
            'total_cents' => $charges->sum('amount_cents'),
            'by_charge_type' => $byChargeType,
        ];
    }
}
