<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Domain\Accounting\Enums\TaxAppliesTo;
use App\Models\Branch;
use App\Models\TaxRule;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TaxRule>
 */
class TaxRuleFactory extends Factory
{
    public function definition(): array
    {
        return [
            'branch_id' => Branch::factory(),
            'name' => 'Occupancy Tax',
            'rate_percent' => 12,
            'applies_to' => TaxAppliesTo::Room,
            'is_active' => true,
        ];
    }
}
