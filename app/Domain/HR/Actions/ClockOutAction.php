<?php

declare(strict_types=1);

namespace App\Domain\HR\Actions;

use App\Models\AttendanceRecord;
use App\Models\Employee;
use Illuminate\Validation\ValidationException;

class ClockOutAction
{
    public function handle(Employee $employee): AttendanceRecord
    {
        $today = now()->toDateString();

        $record = AttendanceRecord::where('employee_id', $employee->id)->whereDate('work_date', $today)->first();

        if ($record === null || $record->clock_in_at === null) {
            throw ValidationException::withMessages(['clock_out' => __('This employee has not clocked in today.')]);
        }

        if ($record->clock_out_at !== null) {
            throw ValidationException::withMessages(['clock_out' => __('This employee has already clocked out today.')]);
        }

        $record->update(['clock_out_at' => now()]);

        return $record;
    }
}
