<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\MenuCategory;
use App\Models\MenuItem;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<MenuItem>
 */
class MenuItemFactory extends Factory
{
    public function definition(): array
    {
        return [
            'menu_category_id' => MenuCategory::factory(),
            'name' => fake()->randomElement(['Grilled Salmon', 'Caesar Salad', 'Club Sandwich', 'Beef Burger', 'Margherita Pizza', 'Chocolate Cake', 'Fresh Orange Juice']),
            'price_cents' => fake()->numberBetween(800, 4500),
            'tax_class' => 'standard',
            'is_available' => true,
        ];
    }
}
