<?php

declare(strict_types=1);

namespace App\Models;

use App\Domain\CRM\Enums\CouponDiscountType;
use App\Domain\CRM\Enums\CouponScope;
use Database\Factories\CouponFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;
use Override;

#[Fillable(['branch_id', 'code', 'name', 'discount_type', 'discount_value', 'scope', 'valid_from', 'valid_until', 'usage_limit', 'is_active'])]
class Coupon extends Model
{
    /** @use HasFactory<CouponFactory> */
    use HasFactory;

    #[Override]
    protected function casts(): array
    {
        return [
            'discount_type' => CouponDiscountType::class,
            'discount_value' => 'integer',
            'scope' => CouponScope::class,
            'valid_from' => 'date',
            'valid_until' => 'date',
            'usage_limit' => 'integer',
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
     * @return HasMany<CouponRedemption, $this>
     */
    public function redemptions(): HasMany
    {
        return $this->hasMany(CouponRedemption::class);
    }

    public function timesUsed(): int
    {
        return $this->redemptions()->count();
    }

    public function isWithinValidityWindow(?Carbon $onDate = null): bool
    {
        $onDate ??= now();

        return $onDate->toDateString() >= $this->valid_from->toDateString()
            && $onDate->toDateString() <= $this->valid_until->toDateString();
    }

    public function hasRemainingUses(): bool
    {
        return $this->usage_limit === null || $this->timesUsed() < $this->usage_limit;
    }

    public function discountCents(int $baseAmountCents): int
    {
        return $this->discount_type === CouponDiscountType::Percent
            ? (int) round($baseAmountCents * ($this->discount_value / 100))
            : min($this->discount_value, $baseAmountCents);
    }
}
