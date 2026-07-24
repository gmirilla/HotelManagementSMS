<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Branch;
use App\Models\EventSpace;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<EventSpace>
 */
class EventSpaceFactory extends Factory
{
    public function definition(): array
    {
        return [
            'branch_id' => Branch::factory(),
            'name' => fake()->randomElement(['Grand Ballroom', 'Executive Boardroom', 'Garden Pavilion']),
            'capacity' => fake()->numberBetween(20, 300),
            'hourly_rate_cents' => fake()->numberBetween(15000, 60000),
            'is_active' => true,
        ];
    }
}
