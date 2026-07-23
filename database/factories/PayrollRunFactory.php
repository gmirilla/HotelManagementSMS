<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Domain\HR\Enums\PayrollRunStatus;
use App\Models\Branch;
use App\Models\PayrollRun;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PayrollRun>
 */
class PayrollRunFactory extends Factory
{
    public function definition(): array
    {
        return [
            'branch_id' => Branch::factory(),
            'period_start' => now()->startOfMonth()->toDateString(),
            'period_end' => now()->endOfMonth()->toDateString(),
            'status' => PayrollRunStatus::Draft,
        ];
    }
}
