<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Domain\Accounting\Enums\AccountType;
use App\Models\Account;
use App\Models\Branch;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Account>
 */
class AccountFactory extends Factory
{
    public function definition(): array
    {
        return [
            'branch_id' => Branch::factory(),
            'code' => (string) fake()->unique()->numberBetween(1000, 9999),
            'name' => fake()->randomElement(['Cash', 'Accounts Receivable', 'Room Revenue', 'Restaurant Revenue', 'Utilities Expense']),
            'account_type' => AccountType::Asset,
            'is_active' => true,
        ];
    }
}
