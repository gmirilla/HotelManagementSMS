<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Domain\Inventory\Enums\StockMovementType;
use App\Models\InventoryItem;
use App\Models\StockMovement;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<StockMovement>
 */
class StockMovementFactory extends Factory
{
    public function definition(): array
    {
        return [
            'inventory_item_id' => InventoryItem::factory(),
            'movement_type' => StockMovementType::Receipt,
            'quantity' => fake()->numberBetween(1, 50),
            'unit_cost_cents' => fake()->numberBetween(100, 2000),
        ];
    }
}
