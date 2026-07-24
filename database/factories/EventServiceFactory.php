<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Domain\Event\Enums\EventServiceCategory;
use App\Models\Branch;
use App\Models\EventService;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<EventService>
 */
class EventServiceFactory extends Factory
{
    public function definition(): array
    {
        return [
            'branch_id' => Branch::factory(),
            'name' => fake()->randomElement(['Buffet Lunch', 'Projector & Screen', 'Coffee Break Service']),
            'category' => EventServiceCategory::Catering,
            'unit_price_cents' => fake()->numberBetween(500, 5000),
            'unit' => 'per_person',
            'is_active' => true,
        ];
    }
}
