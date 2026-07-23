<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Domain\HR\Enums\AttendanceStatus;
use App\Models\AttendanceRecord;
use App\Models\Branch;
use App\Models\Employee;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AttendanceRecord>
 */
class AttendanceRecordFactory extends Factory
{
    public function definition(): array
    {
        $workDate = now()->toDateString();

        return [
            'branch_id' => Branch::factory(),
            'employee_id' => Employee::factory(),
            'work_date' => $workDate,
            'clock_in_at' => $workDate . ' 08:00:00',
            'clock_out_at' => $workDate . ' 17:00:00',
            'status' => AttendanceStatus::Present,
        ];
    }
}
