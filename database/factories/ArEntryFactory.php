<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Domain\Accounting\Enums\ArStatus;
use App\Models\ArEntry;
use App\Models\Branch;
use App\Models\CorporateAccount;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ArEntry>
 */
class ArEntryFactory extends Factory
{
    public function definition(): array
    {
        return [
            'branch_id' => Branch::factory(),
            'corporate_account_id' => CorporateAccount::factory(),
            'amount_cents' => fake()->numberBetween(10000, 500000),
            'paid_cents' => 0,
            'due_date' => now()->addDays(30),
            'status' => ArStatus::Open,
        ];
    }
}
