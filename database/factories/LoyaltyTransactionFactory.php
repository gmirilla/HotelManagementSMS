<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Domain\CRM\Enums\LoyaltyTransactionType;
use App\Models\LoyaltyAccount;
use App\Models\LoyaltyTransaction;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<LoyaltyTransaction>
 */
class LoyaltyTransactionFactory extends Factory
{
    public function definition(): array
    {
        return [
            'loyalty_account_id' => LoyaltyAccount::factory(),
            'transaction_type' => LoyaltyTransactionType::Earn,
            'points' => fake()->numberBetween(50, 500),
            'description' => 'Points earned on stay',
            'transaction_date' => now()->toDateString(),
        ];
    }
}
