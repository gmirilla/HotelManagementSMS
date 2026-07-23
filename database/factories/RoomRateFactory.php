<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Domain\Room\Enums\RoomRateType;
use App\Models\RoomRate;
use App\Models\RoomType;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<RoomRate>
 */
class RoomRateFactory extends Factory
{
    public function definition(): array
    {
        return [
            'room_type_id' => RoomType::factory(),
            'rate_type' => RoomRateType::Base,
            'starts_on' => null,
            'ends_on' => null,
            'days_of_week' => null,
            'rate_cents' => fake()->numberBetween(8000, 45000),
            'priority' => 0,
        ];
    }

    public function weekend(): static
    {
        return $this->state(fn (array $attributes) => [
            'rate_type' => RoomRateType::Weekend,
            'days_of_week' => [5, 6],
            'priority' => 10,
        ]);
    }

    public function seasonal(): static
    {
        return $this->state(fn (array $attributes) => [
            'rate_type' => RoomRateType::Seasonal,
            'starts_on' => now()->addMonth()->startOfMonth(),
            'ends_on' => now()->addMonth()->endOfMonth(),
            'priority' => 20,
        ]);
    }
}
