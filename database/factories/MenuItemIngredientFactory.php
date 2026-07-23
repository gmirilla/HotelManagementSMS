<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\InventoryItem;
use App\Models\MenuItem;
use App\Models\MenuItemIngredient;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<MenuItemIngredient>
 */
class MenuItemIngredientFactory extends Factory
{
    public function definition(): array
    {
        return [
            'menu_item_id' => MenuItem::factory(),
            'inventory_item_id' => InventoryItem::factory(),
            'quantity' => fake()->randomFloat(3, 0.1, 2),
            'unit' => 'kg',
        ];
    }
}
