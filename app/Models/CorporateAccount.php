<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\CorporateAccountFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Override;

#[Fillable(['tenant_id', 'company_name', 'billing_email', 'negotiated_rate_cents', 'direct_billing_enabled'])]
class CorporateAccount extends Model
{
    /** @use HasFactory<CorporateAccountFactory> */
    use HasFactory, SoftDeletes;

    #[Override]
    protected function casts(): array
    {
        return [
            'negotiated_rate_cents' => 'integer',
            'direct_billing_enabled' => 'boolean',
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
     * @return HasMany<Reservation, $this>
     */
    public function reservations(): HasMany
    {
        return $this->hasMany(Reservation::class);
    }

    /**
     * @return HasMany<ArEntry, $this>
     */
    public function arEntries(): HasMany
    {
        return $this->hasMany(ArEntry::class);
    }
}
