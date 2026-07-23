<?php

declare(strict_types=1);

namespace App\Models;

use App\Domain\FrontDesk\Enums\FolioStatus;
use Database\Factories\FolioFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Override;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

#[Fillable(['branch_id', 'reservation_id', 'guest_id', 'status', 'balance_cents', 'closed_at'])]
class Folio extends Model
{
    /** @use HasFactory<FolioFactory> */
    use HasFactory, LogsActivity;

    #[Override]
    protected function casts(): array
    {
        return [
            'status' => FolioStatus::class,
            'balance_cents' => 'integer',
            'closed_at' => 'datetime',
        ];
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['status', 'balance_cents'])
            ->logOnlyDirty()
            ->useLogName('folio');
    }

    /**
     * @return BelongsTo<Branch, $this>
     */
    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    /**
     * @return BelongsTo<Reservation, $this>
     */
    public function reservation(): BelongsTo
    {
        return $this->belongsTo(Reservation::class);
    }

    /**
     * @return BelongsTo<Guest, $this>
     */
    public function guest(): BelongsTo
    {
        return $this->belongsTo(Guest::class);
    }

    /**
     * @return HasMany<FolioCharge, $this>
     */
    public function charges(): HasMany
    {
        return $this->hasMany(FolioCharge::class);
    }

    /**
     * @return HasMany<Payment, $this>
     */
    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function isOpen(): bool
    {
        return $this->status === FolioStatus::Open;
    }
}
