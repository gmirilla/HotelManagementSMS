<?php

declare(strict_types=1);

namespace App\Domain\Event\Support;

use App\Models\EventBooking;
use App\Models\EventBookingItem;

/**
 * Produces the consolidated bill (FR-EVT-003): venue rental for the booked
 * duration plus every catering/equipment/service line item. Always computed
 * from the booking's stored duration and its items — never cached.
 */
class EventBillCalculator
{
    /**
     * @return array{venue_cents: int, items: array<int, array{item: EventBookingItem, line_total_cents: int}>, items_total_cents: int, total_cents: int}
     */
    public function calculate(EventBooking $booking): array
    {
        $venueCents = (int) round($booking->eventSpace->hourly_rate_cents * $booking->durationHours());

        $items = $booking->items->map(fn ($item) => [
            'item' => $item,
            'line_total_cents' => $item->lineTotalCents(),
        ]);

        $itemsTotalCents = $items->sum('line_total_cents');

        return [
            'venue_cents' => $venueCents,
            'items' => $items->all(),
            'items_total_cents' => $itemsTotalCents,
            'total_cents' => $venueCents + $itemsTotalCents,
        ];
    }
}
