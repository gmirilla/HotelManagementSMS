<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Domain\Restaurant\Enums\KitchenStatus;
use App\Models\MenuItem;
use App\Models\RestaurantOrder;
use App\Models\RestaurantOrderItem;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<RestaurantOrderItem>
 */
class RestaurantOrderItemFactory extends Factory
{
    public function definition(): array
    {
        return [
            'order_id' => RestaurantOrder::factory(),
            'menu_item_id' => MenuItem::factory(),
            'quantity' => fake()->numberBetween(1, 3),
            'unit_price_cents' => fake()->numberBetween(800, 4500),
            'kitchen_status' => KitchenStatus::Queued,
        ];
    }
}
