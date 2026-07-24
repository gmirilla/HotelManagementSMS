<?php

declare(strict_types=1);

namespace App\Domain\Event\Actions;

use App\Models\EventBooking;
use App\Models\EventBookingItem;
use App\Models\EventService;
use Illuminate\Validation\ValidationException;

class AddEventBookingItemAction
{
    public function handle(EventBooking $booking, EventService $service, int $quantity): EventBookingItem
    {
        if ($quantity <= 0) {
            throw ValidationException::withMessages(['quantity' => __('Quantity must be greater than zero.')]);
        }

        return $booking->items()->create([
            'event_service_id' => $service->id,
            'quantity' => $quantity,
            'unit_price_cents' => $service->unit_price_cents,
        ]);
    }
}
