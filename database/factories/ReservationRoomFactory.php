<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Reservation;
use App\Models\ReservationRoom;
use App\Models\RoomType;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ReservationRoom>
 */
class ReservationRoomFactory extends Factory
{
    public function definition(): array
    {
        return [
            'reservation_id' => Reservation::factory(),
            'room_type_id' => RoomType::factory(),
            'room_id' => null,
            'rate_cents' => fake()->numberBetween(8000, 45000),
            'occupants_adults' => fake()->numberBetween(1, 2),
            'occupants_children' => 0,
        ];
    }
}
