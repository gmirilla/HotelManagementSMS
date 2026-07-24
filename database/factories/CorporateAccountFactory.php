<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Domain\CRM\Enums\CorporateAccountType;
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
            'account_type' => CorporateAccountType::Corporate,
            'billing_email' => fake()->companyEmail(),
            'negotiated_rate_cents' => fake()->numberBetween(7000, 30000),
            'direct_billing_enabled' => fake()->boolean(60),
        ];
    }

    public function travelAgent(): static
    {
        return $this->state(fn (array $attributes): array => [
            'account_type' => CorporateAccountType::TravelAgent,
            'commission_percent' => fake()->randomElement([8, 10, 12, 15]),
            'negotiated_rate_cents' => null,
        ]);
    }
}
