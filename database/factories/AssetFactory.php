<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Asset;
use App\Models\Branch;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Asset>
 */
class AssetFactory extends Factory
{
    public function definition(): array
    {
        return [
            'branch_id' => Branch::factory(),
            'name' => fake()->randomElement(['HVAC Unit', 'Elevator', 'Washing Machine', 'Generator', 'Boiler']) . ' #' . fake()->numberBetween(1, 20),
            'serial_number' => strtoupper(fake()->bothify('SN-####??')),
            'purchased_on' => fake()->dateTimeBetween('-5 years', '-1 year'),
            'warranty_expires_on' => fake()->dateTimeBetween('now', '+2 years'),
            'location' => fake()->randomElement(['Basement', 'Roof', 'Laundry Room', 'Utility Closet']),
        ];
    }
}
