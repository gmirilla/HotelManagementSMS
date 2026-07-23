<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Domain\Reservation\Enums\WaitlistStatus;
use App\Models\Branch;
use App\Models\Guest;
use App\Models\RoomType;
use App\Models\WaitlistEntry;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<WaitlistEntry>
 */
class WaitlistEntryFactory extends Factory
{
    public function definition(): array
    {
        $arrival = fake()->dateTimeBetween('+1 week', '+2 months');
        $departure = (clone $arrival)->modify('+' . fake()->numberBetween(1, 5) . ' days');

        return [
            'branch_id' => Branch::factory(),
            'guest_id' => Guest::factory(),
            'room_type_id' => RoomType::factory(),
            'desired_arrival' => $arrival,
            'desired_departure' => $departure,
            'status' => WaitlistStatus::Waiting,
        ];
    }
}
