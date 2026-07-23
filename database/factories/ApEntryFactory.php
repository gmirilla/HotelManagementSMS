<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Domain\Accounting\Enums\ApStatus;
use App\Models\ApEntry;
use App\Models\Branch;
use App\Models\Supplier;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ApEntry>
 */
class ApEntryFactory extends Factory
{
    public function definition(): array
    {
        return [
            'branch_id' => Branch::factory(),
            'supplier_id' => Supplier::factory(),
            'amount_cents' => fake()->numberBetween(10000, 300000),
            'paid_cents' => 0,
            'due_date' => now()->addDays(30),
            'status' => ApStatus::Open,
        ];
    }
}
