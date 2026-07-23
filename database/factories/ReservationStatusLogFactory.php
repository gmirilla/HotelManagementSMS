<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Reservation;
use App\Models\ReservationStatusLog;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ReservationStatusLog>
 */
class ReservationStatusLogFactory extends Factory
{
    public function definition(): array
    {
        return [
            'reservation_id' => Reservation::factory(),
            'from_status' => 'pending',
            'to_status' => 'confirmed',
            'changed_by_user_id' => null,
            'reason' => null,
        ];
    }
}
