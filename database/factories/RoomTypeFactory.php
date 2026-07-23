<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Branch;
use App\Models\RoomType;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<RoomType>
 */
class RoomTypeFactory extends Factory
{
    public function definition(): array
    {
        $name = fake()->randomElement(['Standard', 'Deluxe', 'Executive Suite', 'Family Room', 'Presidential Suite']);

        return [
            'branch_id' => Branch::factory(),
            'name' => $name,
            'slug' => Str::slug($name),
            'base_capacity_adults' => fake()->numberBetween(1, 4),
            'base_capacity_children' => fake()->numberBetween(0, 2),
            'base_rate_cents' => fake()->numberBetween(8000, 45000),
            'description' => fake()->sentence(12),
            'is_active' => true,
        ];
    }
}
