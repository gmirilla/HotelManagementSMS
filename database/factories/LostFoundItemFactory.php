<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Domain\Housekeeping\Enums\LostFoundStatus;
use App\Models\Branch;
use App\Models\LostFoundItem;
use App\Models\Room;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<LostFoundItem>
 */
class LostFoundItemFactory extends Factory
{
    public function definition(): array
    {
        return [
            'branch_id' => Branch::factory(),
            'room_id' => Room::factory(),
            'description' => fake()->randomElement(['Phone charger', 'Sunglasses', 'Book', 'Jacket', 'Umbrella']),
            'found_by_user_id' => User::factory(),
            'found_on' => now()->toDateString(),
            'status' => LostFoundStatus::Held,
        ];
    }
}
