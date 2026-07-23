<?php

declare(strict_types=1);

namespace App\Domain\Reservation\Support;

use App\Domain\Room\Enums\RoomStatus;
use App\Models\ReservationRoom;
use App\Models\Room;
use App\Models\RoomType;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;

/**
 * Availability is tracked at the room-TYPE level, not against specific
 * physical rooms — `reservation_rooms.room_id` stays null until a physical
 * room is assigned (typically at check-in, see erd.md §4), so counting
 * inventory by type is the only way to know what's bookable before then.
 */
class AvailabilityChecker
{
    /**
     * Rooms permanently pulled from service block inventory outright;
     * "occupied" does not, because that reflects today's state, not the
     * requested date range.
     */
    private const array UNBOOKABLE_STATUSES = [RoomStatus::OutOfOrder, RoomStatus::OutOfService];

    public function availableRoomCount(RoomType $roomType, Carbon $arrival, Carbon $departure): int
    {
        $totalRooms = Room::query()
            ->where('branch_id', $roomType->branch_id)
            ->where('room_type_id', $roomType->id)
            ->where('is_active', true)
            ->whereNotIn('status', array_map(fn (RoomStatus $status) => $status->value, self::UNBOOKABLE_STATUSES))
            ->count();

        $reservedCount = $this->overlappingReservationRoomsQuery($roomType->id, $arrival, $departure)->count();

        return max(0, $totalRooms - $reservedCount);
    }

    public function isAvailable(RoomType $roomType, Carbon $arrival, Carbon $departure): bool
    {
        return $this->availableRoomCount($roomType, $arrival, $departure) > 0;
    }

    /**
     * @return Builder<ReservationRoom>
     */
    private function overlappingReservationRoomsQuery(int $roomTypeId, Carbon $arrival, Carbon $departure): Builder
    {
        return ReservationRoom::query()
            ->where('room_type_id', $roomTypeId)
            ->whereHas('reservation', function ($query) use ($arrival, $departure) {
                $query->whereIn('status', ['pending', 'confirmed', 'checked_in'])
                    ->where('arrival_date', '<', $departure)
                    ->where('departure_date', '>', $arrival);
            });
    }
}
