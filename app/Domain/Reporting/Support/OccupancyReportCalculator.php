<?php

declare(strict_types=1);

namespace App\Domain\Reporting\Support;

use App\Domain\Reservation\Enums\ReservationStatus;
use App\Models\ReservationRoom;
use App\Models\Room;
use Illuminate\Support\Carbon;

/**
 * Occupancy is always computed for a specific date from the reservation
 * ledger — never a stored daily snapshot — the same reasoning as every
 * other "ledger is truth" calculator in this codebase.
 */
class OccupancyReportCalculator
{
    /**
     * @return array{date: string, total_rooms: int, occupied_rooms: int, occupancy_rate: float}
     */
    public function forDate(int $branchId, Carbon $date): array
    {
        $totalRooms = Room::where('branch_id', $branchId)->where('is_active', true)->count();

        // Each ReservationRoom row represents one reserved room of a given
        // type — room_id itself stays null until check-in (see
        // AvailabilityChecker), so inventory is counted by row, not by a
        // distinct assigned room.
        $occupiedRooms = ReservationRoom::whereHas('reservation', function ($query) use ($branchId, $date) {
            $query->where('branch_id', $branchId)
                ->whereIn('status', [ReservationStatus::Pending, ReservationStatus::Confirmed, ReservationStatus::CheckedIn])
                ->where('arrival_date', '<=', $date->toDateString())
                ->where('departure_date', '>', $date->toDateString());
        })->count();

        return [
            'date' => $date->toDateString(),
            'total_rooms' => $totalRooms,
            'occupied_rooms' => $occupiedRooms,
            'occupancy_rate' => $totalRooms > 0 ? round($occupiedRooms / $totalRooms, 4) : 0.0,
        ];
    }
}
