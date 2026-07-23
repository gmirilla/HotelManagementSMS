<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\InventoryItem;
use App\Models\Warehouse;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<InventoryItem>
 */
class InventoryItemFactory extends Factory
{
    public function definition(): array
    {
        return [
            'warehouse_id' => Warehouse::factory(),
            'sku' => strtoupper(fake()->unique()->bothify('SKU-####')),
            'name' => fake()->randomElement(['Bath Towels', 'Bottled Water', 'Coffee Beans', 'Eggs', 'Fresh Milk', 'Cleaning Spray', 'Toilet Paper', 'Chicken Breast']),
            'unit_of_measure' => fake()->randomElement(['unit', 'kg', 'liter', 'box']),
            'barcode' => fake()->ean13(),
            'reorder_point' => 20,
            'quantity_on_hand' => 0,
            'average_cost_cents' => fake()->numberBetween(100, 5000),
            'is_perishable' => false,
        ];
    }

    public function perishable(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_perishable' => true,
            'expires_on' => fake()->dateTimeBetween('+1 day', '+30 days'),
        ]);
    }

    /**
     * quantity_on_hand is always recomputed from the stock_movements ledger
     * (see InventoryQuantityCalculator), so simply setting the column via
     * ->create(['quantity_on_hand' => N]) gets silently overwritten the next
     * time any stock Action runs. Use this state to seed a real opening-stock
     * movement backing that quantity instead.
     */
    public function withOpeningStock(int $quantity, int $unitCostCents = 100): static
    {
        return $this->state(['quantity_on_hand' => $quantity, 'average_cost_cents' => $unitCostCents])
            ->afterCreating(function (InventoryItem $item) use ($quantity, $unitCostCents) {
                $item->stockMovements()->create([
                    'movement_type' => 'receipt',
                    'quantity' => $quantity,
                    'unit_cost_cents' => $unitCostCents,
                    'notes' => 'Test opening stock',
                ]);
            });
    }
}
