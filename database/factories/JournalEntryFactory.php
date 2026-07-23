<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Branch;
use App\Models\JournalEntry;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<JournalEntry>
 */
class JournalEntryFactory extends Factory
{
    public function definition(): array
    {
        return [
            'branch_id' => Branch::factory(),
            'entry_date' => now()->toDateString(),
            'memo' => fake()->sentence(6),
        ];
    }
}
