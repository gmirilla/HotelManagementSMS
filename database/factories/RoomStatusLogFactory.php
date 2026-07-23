<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Room;
use App\Models\RoomStatusLog;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<RoomStatusLog>
 */
class RoomStatusLogFactory extends Factory
{
    public function definition(): array
    {
        return [
            'room_id' => Room::factory(),
            'from_status' => 'vacant_dirty',
            'to_status' => 'vacant_clean',
            'changed_by_user_id' => null,
            'reason' => 'Routine housekeeping',
        ];
    }
}
