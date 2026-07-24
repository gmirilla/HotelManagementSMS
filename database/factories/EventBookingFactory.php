<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Domain\Event\Enums\EventBookingStatus;
use App\Models\Branch;
use App\Models\EventBooking;
use App\Models\EventSpace;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<EventBooking>
 */
class EventBookingFactory extends Factory
{
    public function definition(): array
    {
        $start = fake()->dateTimeBetween('+1 week', '+2 months');

        return [
            'branch_id' => Branch::factory(),
            'event_space_id' => EventSpace::factory(),
            'title' => fake()->words(3, true),
            'event_type' => fake()->randomElement(['conference', 'wedding', 'banquet']),
            'start_at' => $start,
            'end_at' => (clone $start)->modify('+4 hours'),
            'attendee_count' => fake()->numberBetween(20, 150),
            'status' => EventBookingStatus::Tentative,
        ];
    }
}
