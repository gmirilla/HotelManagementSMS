<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\CorporateAccount;
use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CorporateAccount>
 */
class CorporateAccountFactory extends Factory
{
    public function definition(): array
    {
        return [
            'tenant_id' => Tenant::factory(),
            'company_name' => fake()->company(),
            'billing_email' => fake()->companyEmail(),
            'negotiated_rate_cents' => fake()->numberBetween(7000, 30000),
            'direct_billing_enabled' => fake()->boolean(60),
        ];
    }
}
