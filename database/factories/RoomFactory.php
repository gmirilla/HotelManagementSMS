<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Domain\Room\Enums\HousekeepingStatus;
use App\Domain\Room\Enums\RoomStatus;
use App\Models\Branch;
use App\Models\Room;
use App\Models\RoomType;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Room>
 */
class RoomFactory extends Factory
{
    public function definition(): array
    {
        $floor = (string) fake()->numberBetween(1, 12);

        return [
            'branch_id' => Branch::factory(),
            'room_type_id' => RoomType::factory(),
            'room_number' => $floor . fake()->unique()->numerify('##'),
            'building' => 'Main',
            'floor' => $floor,
            'status' => RoomStatus::VacantClean,
            'housekeeping_status' => HousekeepingStatus::Clean,
            'is_active' => true,
        ];
    }
}
