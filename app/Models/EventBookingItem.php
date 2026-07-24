<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\EventBookingItemFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Override;

#[Fillable(['event_booking_id', 'event_service_id', 'quantity', 'unit_price_cents'])]
class EventBookingItem extends Model
{
    /** @use HasFactory<EventBookingItemFactory> */
    use HasFactory;

    #[Override]
    protected function casts(): array
    {
        return [
            'quantity' => 'integer',
            'unit_price_cents' => 'integer',
        ];
    }

    /**
     * @return BelongsTo<EventBooking, $this>
     */
    public function eventBooking(): BelongsTo
    {
        return $this->belongsTo(EventBooking::class);
    }

    /**
     * @return BelongsTo<EventService, $this>
     */
    public function eventService(): BelongsTo
    {
        return $this->belongsTo(EventService::class);
    }

    public function lineTotalCents(): int
    {
        return $this->quantity * $this->unit_price_cents;
    }
}
