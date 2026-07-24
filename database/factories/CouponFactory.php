<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Domain\CRM\Enums\CouponDiscountType;
use App\Domain\CRM\Enums\CouponScope;
use App\Models\Branch;
use App\Models\Coupon;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Coupon>
 */
class CouponFactory extends Factory
{
    public function definition(): array
    {
        return [
            'branch_id' => Branch::factory(),
            'code' => Str::upper(fake()->unique()->bothify('SAVE##??')),
            'name' => fake()->words(3, true),
            'discount_type' => CouponDiscountType::Percent,
            'discount_value' => 10,
            'scope' => CouponScope::All,
            'valid_from' => now()->subDays(5)->toDateString(),
            'valid_until' => now()->addMonths(2)->toDateString(),
            'is_active' => true,
        ];
    }
}
