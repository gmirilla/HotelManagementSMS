<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Domain\Accounting\Enums\JournalSide;
use App\Models\Account;
use App\Models\JournalEntry;
use App\Models\JournalLine;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<JournalLine>
 */
class JournalLineFactory extends Factory
{
    public function definition(): array
    {
        return [
            'journal_entry_id' => JournalEntry::factory(),
            'account_id' => Account::factory(),
            'side' => JournalSide::Debit,
            'amount_cents' => fake()->numberBetween(1000, 100000),
        ];
    }
}
