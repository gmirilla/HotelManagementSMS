<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Domain\Restaurant\Enums\OutletType;
use App\Models\Branch;
use App\Models\RestaurantOutlet;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<RestaurantOutlet>
 */
class RestaurantOutletFactory extends Factory
{
    public function definition(): array
    {
        return [
            'branch_id' => Branch::factory(),
            'name' => fake()->randomElement(['The Grand Dining Room', 'Skyline Bar', 'Poolside Grill']),
            'outlet_type' => OutletType::Restaurant,
        ];
    }
}
