<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\CouponRedemptionFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Override;

#[Fillable(['coupon_id', 'reference_type', 'reference_id', 'discount_applied_cents', 'redeemed_by_user_id', 'redeemed_at'])]
class CouponRedemption extends Model
{
    /** @use HasFactory<CouponRedemptionFactory> */
    use HasFactory;

    #[Override]
    protected function casts(): array
    {
        return [
            'discount_applied_cents' => 'integer',
            'redeemed_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<Coupon, $this>
     */
    public function coupon(): BelongsTo
    {
        return $this->belongsTo(Coupon::class);
    }

    /**
     * @return MorphTo<Model, $this>
     */
    public function reference(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function redeemedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'redeemed_by_user_id');
    }
}
