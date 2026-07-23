<?php

declare(strict_types=1);

namespace App\Models;

use App\Domain\Reservation\Enums\ReservationSource;
use App\Domain\Reservation\Enums\ReservationStatus;
use Database\Factories\ReservationFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Override;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

#[Fillable([
    'branch_id', 'guest_id', 'corporate_account_id', 'confirmation_code', 'source', 'status',
    'arrival_date', 'departure_date', 'adults', 'children', 'special_requests',
    'cancellation_fee_cents', 'cancelled_at',
])]
class Reservation extends Model
{
    /** @use HasFactory<ReservationFactory> */
    use HasFactory, LogsActivity, SoftDeletes;

    #[Override]
    protected function casts(): array
    {
        return [
            'source' => ReservationSource::class,
            'status' => ReservationStatus::class,
            'arrival_date' => 'date',
            'departure_date' => 'date',
            'adults' => 'integer',
            'children' => 'integer',
            'cancellation_fee_cents' => 'integer',
            'cancelled_at' => 'datetime',
        ];
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['status', 'arrival_date', 'departure_date', 'cancellation_fee_cents'])
            ->logOnlyDirty()
            ->useLogName('reservation');
    }

    /**
     * @return BelongsTo<Branch, $this>
     */
    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
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
     * @return HasMany<ReservationRoom, $this>
     */
    public function rooms(): HasMany
    {
        return $this->hasMany(ReservationRoom::class);
    }

    /**
     * @return HasMany<ReservationStatusLog, $this>
     */
    public function statusLogs(): HasMany
    {
        return $this->hasMany(ReservationStatusLog::class);
    }

    /**
     * @return HasOne<Folio, $this>
     */
    public function folio(): HasOne
    {
        return $this->hasOne(Folio::class);
    }

    public function nights(): int
    {
        return (int) $this->arrival_date->diffInDays($this->departure_date);
    }
}
