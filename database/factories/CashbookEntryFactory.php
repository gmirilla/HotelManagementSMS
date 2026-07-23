<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Domain\Accounting\Enums\CashbookEntryType;
use App\Models\Branch;
use App\Models\CashbookEntry;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CashbookEntry>
 */
class CashbookEntryFactory extends Factory
{
    public function definition(): array
    {
        return [
            'branch_id' => Branch::factory(),
            'cashier_user_id' => User::factory(),
            'entry_type' => CashbookEntryType::CashIn,
            'amount_cents' => fake()->numberBetween(500, 50000),
            'reason' => fake()->sentence(4),
            'shift_date' => now()->toDateString(),
        ];
    }
}
