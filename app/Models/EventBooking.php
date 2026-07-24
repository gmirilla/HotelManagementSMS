<?php

declare(strict_types=1);

namespace App\Models;

use App\Domain\Event\Enums\EventBookingStatus;
use Database\Factories\EventBookingFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Override;

#[Fillable([
    'branch_id', 'event_space_id', 'guest_id', 'corporate_account_id', 'title', 'event_type',
    'start_at', 'end_at', 'attendee_count', 'status', 'notes', 'created_by_user_id',
])]
class EventBooking extends Model
{
    /** @use HasFactory<EventBookingFactory> */
    use HasFactory;

    #[Override]
    protected function casts(): array
    {
        return [
            'start_at' => 'datetime',
            'end_at' => 'datetime',
            'attendee_count' => 'integer',
            'status' => EventBookingStatus::class,
        ];
    }

    /**
     * @return BelongsTo<Branch, $this>
     */
    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    /**
     * @return BelongsTo<EventSpace, $this>
     */
    public function eventSpace(): BelongsTo
    {
        return $this->belongsTo(EventSpace::class);
    }

    /**
     * @return BelongsTo<Guest, $this>
     */
    public function guest(): BelongsTo
    {
        return $this->belongsTo(Guest::class);
    }

    /**
     * @return BelongsTo<CorporateAccount, $this>
     */
    public function corporateAccount(): BelongsTo
    {
        return $this->belongsTo(CorporateAccount::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    /**
     * @return HasMany<EventBookingItem, $this>
     */
    public function items(): HasMany
    {
        return $this->hasMany(EventBookingItem::class);
    }

    public function durationHours(): float
    {
        return round($this->start_at->diffInMinutes($this->end_at) / 60, 2);
    }
}
