<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\InventoryItem;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderItem;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PurchaseOrderItem>
 */
class PurchaseOrderItemFactory extends Factory
{
    public function definition(): array
    {
        return [
            'purchase_order_id' => PurchaseOrder::factory(),
            'inventory_item_id' => InventoryItem::factory(),
            'quantity_ordered' => fake()->numberBetween(10, 100),
            'quantity_received' => 0,
            'unit_cost_cents' => fake()->numberBetween(100, 2000),
        ];
    }
}
