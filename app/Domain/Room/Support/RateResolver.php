<?php

declare(strict_types=1);

namespace App\Domain\Room\Support;

use App\Models\RoomRate;
use App\Models\RoomType;
use Illuminate\Support\Carbon;

/**
 * Resolves the nightly rate for a room type on a specific date, applying the
 * precedence documented in docs/architecture/erd.md §2: override > holiday >
 * seasonal > weekend > base — implemented generically via each RoomRate row's
 * `priority` column rather than hardcoding the type order, so a deployment
 * can reprioritize without a code change.
 */
class RateResolver
{
    public function nightlyRateCents(RoomType $roomType, Carbon $date): int
    {
        $applicable = $roomType->rates
            ->filter(fn ($rate) => $this->applies($rate, $date))
            ->sortByDesc('priority');

        return $applicable->first()?->rate_cents ?? $roomType->base_rate_cents;
    }

    /**
     * @return array<string, int> date (Y-m-d) => nightly rate in cents
     */
    public function nightlyRatesForStay(RoomType $roomType, Carbon $arrival, Carbon $departure): array
    {
        $rates = [];
        $cursor = $arrival->copy();

        while ($cursor->lt($departure)) {
            $rates[$cursor->toDateString()] = $this->nightlyRateCents($roomType, $cursor);
            $cursor->addDay();
        }

        return $rates;
    }

    private function applies(RoomRate $rate, Carbon $date): bool
    {
        if ($rate->rate_type->value === 'base') {
            return true;
        }

        if ($rate->starts_on && $rate->ends_on) {
            return $date->between($rate->starts_on, $rate->ends_on);
        }

        if ($rate->days_of_week) {
            return in_array($date->dayOfWeekIso % 7, $rate->days_of_week, true);
        }

        return false;
    }
}
