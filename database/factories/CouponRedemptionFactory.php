<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Coupon;
use App\Models\CouponRedemption;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CouponRedemption>
 */
class CouponRedemptionFactory extends Factory
{
    public function definition(): array
    {
        return [
            'coupon_id' => Coupon::factory(),
            'discount_applied_cents' => fake()->numberBetween(500, 5000),
            'redeemed_at' => now(),
        ];
    }
}
