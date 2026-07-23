<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\MenuCategory;
use App\Models\RestaurantOutlet;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<MenuCategory>
 */
class MenuCategoryFactory extends Factory
{
    public function definition(): array
    {
        return [
            'outlet_id' => RestaurantOutlet::factory(),
            'name' => fake()->randomElement(['Starters', 'Main Courses', 'Desserts', 'Beverages']),
            'display_order' => fake()->numberBetween(0, 10),
        ];
    }
}
