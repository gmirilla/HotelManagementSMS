<?php

declare(strict_types=1);

namespace App\Domain\CRM\Actions;

use App\Domain\CRM\Enums\CouponScope;
use App\Models\Coupon;
use App\Models\CouponRedemption;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\ValidationException;

class RedeemCouponAction
{
    public function handle(Coupon $coupon, CouponScope $requiredScope, int $baseAmountCents, ?Model $reference = null, ?User $redeemedBy = null): CouponRedemption
    {
        if (! $coupon->is_active) {
            throw ValidationException::withMessages(['coupon' => __('This coupon is not active.')]);
        }

        if (! $coupon->isWithinValidityWindow()) {
            throw ValidationException::withMessages(['coupon' => __('This coupon is outside its validity window.')]);
        }

        if (! $coupon->hasRemainingUses()) {
            throw ValidationException::withMessages(['coupon' => __('This coupon has reached its usage limit.')]);
        }

        if ($coupon->scope !== CouponScope::All && $coupon->scope !== $requiredScope) {
            throw ValidationException::withMessages(['coupon' => __('This coupon does not apply to this type of purchase.')]);
        }

        return $coupon->redemptions()->create([
            'reference_type' => $reference?->getMorphClass(),
            'reference_id' => $reference?->getKey(),
            'discount_applied_cents' => $coupon->discountCents($baseAmountCents),
            'redeemed_by_user_id' => $redeemedBy?->id,
            'redeemed_at' => now(),
        ]);
    }
}
