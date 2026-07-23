<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Domain\HR\Enums\LeaveRequestStatus;
use App\Models\Employee;
use App\Models\LeaveRequest;
use App\Models\LeaveType;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<LeaveRequest>
 */
class LeaveRequestFactory extends Factory
{
    public function definition(): array
    {
        $start = fake()->dateTimeBetween('now', '+2 months');
        $end = (clone $start)->modify('+2 days');

        return [
            'employee_id' => Employee::factory(),
            'leave_type_id' => LeaveType::factory(),
            'start_date' => $start,
            'end_date' => $end,
            'days_requested' => 3,
            'reason' => fake()->sentence(6),
            'status' => LeaveRequestStatus::Pending,
        ];
    }
}
