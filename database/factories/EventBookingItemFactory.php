<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\EventBooking;
use App\Models\EventBookingItem;
use App\Models\EventService;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<EventBookingItem>
 */
class EventBookingItemFactory extends Factory
{
    public function definition(): array
    {
        return [
            'event_booking_id' => EventBooking::factory(),
            'event_service_id' => EventService::factory(),
            'quantity' => fake()->numberBetween(1, 100),
            'unit_price_cents' => fake()->numberBetween(500, 5000),
        ];
    }
}
