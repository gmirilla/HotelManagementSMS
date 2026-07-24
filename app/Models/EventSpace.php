<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\EventSpaceFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Override;

#[Fillable(['branch_id', 'name', 'capacity', 'layout_options', 'hourly_rate_cents', 'is_active'])]
class EventSpace extends Model
{
    /** @use HasFactory<EventSpaceFactory> */
    use HasFactory;

    #[Override]
    protected function casts(): array
    {
        return [
            'capacity' => 'integer',
            'layout_options' => 'array',
            'hourly_rate_cents' => 'integer',
            'is_active' => 'boolean',
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
     * @return HasMany<EventBooking, $this>
     */
    public function bookings(): HasMany
    {
        return $this->hasMany(EventBooking::class);
    }
}
