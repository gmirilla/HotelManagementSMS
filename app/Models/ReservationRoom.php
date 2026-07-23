<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\ReservationRoomFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Override;

#[Fillable([
    'reservation_id', 'room_type_id', 'room_id', 'rate_cents',
    'occupants_adults', 'occupants_children',
])]
class ReservationRoom extends Model
{
    /** @use HasFactory<ReservationRoomFactory> */
    use HasFactory;

    #[Override]
    protected function casts(): array
    {
        return [
            'rate_cents' => 'integer',
            'occupants_adults' => 'integer',
            'occupants_children' => 'integer',
        ];
    }

    /**
     * @return BelongsTo<Reservation, $this>
     */
    public function reservation(): BelongsTo
    {
        return $this->belongsTo(Reservation::class);
    }

    /**
     * @return BelongsTo<RoomType, $this>
     */
    public function roomType(): BelongsTo
    {
        return $this->belongsTo(RoomType::class);
    }

    /**
     * @return BelongsTo<Room, $this>
     */
    public function room(): BelongsTo
    {
        return $this->belongsTo(Room::class);
    }
}
