<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Domain\Restaurant\Enums\TableStatus;
use App\Models\RestaurantOutlet;
use App\Models\RestaurantTable;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<RestaurantTable>
 */
class RestaurantTableFactory extends Factory
{
    public function definition(): array
    {
        return [
            'outlet_id' => RestaurantOutlet::factory(),
            'label' => (string) fake()->unique()->numberBetween(1, 40),
            'seats' => fake()->randomElement([2, 2, 4, 4, 6, 8]),
            'status' => TableStatus::Free,
        ];
    }
}
