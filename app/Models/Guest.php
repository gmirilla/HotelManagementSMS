<?php

declare(strict_types=1);

namespace App\Models;

use App\Domain\Guest\Enums\GuestFlag;
use App\Domain\Guest\Enums\GuestType;
use Database\Factories\GuestFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Override;

#[Fillable([
    'tenant_id', 'user_id', 'first_name', 'last_name', 'email', 'phone', 'date_of_birth',
    'nationality', 'guest_type', 'flag', 'blacklist_reason', 'preferences',
])]
class Guest extends Model
{
    /** @use HasFactory<GuestFactory> */
    use HasFactory, SoftDeletes;

    #[Override]
    protected function casts(): array
    {
        return [
            'date_of_birth' => 'date',
            'guest_type' => GuestType::class,
            'flag' => GuestFlag::class,
            'preferences' => 'array',
        ];
    }

    /**
     * @return BelongsTo<Tenant, $this>
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return HasMany<GuestDocument, $this>
     */
    public function documents(): HasMany
    {
        return $this->hasMany(GuestDocument::class);
    }

    /**
     * @return HasMany<GuestContact, $this>
     */
    public function contacts(): HasMany
    {
        return $this->hasMany(GuestContact::class);
    }

    /**
     * @return HasMany<GuestNote, $this>
     */
    public function notes(): HasMany
    {
        return $this->hasMany(GuestNote::class);
    }

    /**
     * @return HasMany<Reservation, $this>
     */
    public function reservations(): HasMany
    {
        return $this->hasMany(Reservation::class);
    }

    /**
     * @return HasOne<Folio, $this>
     */
    public function openFolio(): HasOne
    {
        return $this->hasOne(Folio::class)->where('status', 'open');
    }

    public function fullName(): string
    {
        return trim("{$this->first_name} {$this->last_name}");
    }

    public function isBlacklisted(): bool
    {
        return $this->flag === GuestFlag::Blacklisted;
    }
}
