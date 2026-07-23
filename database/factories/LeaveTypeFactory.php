<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Branch;
use App\Models\LeaveType;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<LeaveType>
 */
class LeaveTypeFactory extends Factory
{
    public function definition(): array
    {
        return [
            'branch_id' => Branch::factory(),
            'name' => fake()->randomElement(['Annual Leave', 'Sick Leave', 'Unpaid Leave']),
            'days_per_year' => 21,
            'is_paid' => true,
            'is_active' => true,
        ];
    }
}
