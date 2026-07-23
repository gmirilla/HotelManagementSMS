<?php

declare(strict_types=1);

namespace App\Domain\HR\Actions;

use App\Domain\HR\Enums\AttendanceStatus;
use App\Models\AttendanceRecord;
use App\Models\Employee;
use Illuminate\Validation\ValidationException;

class ClockInAction
{
    public function handle(Employee $employee): AttendanceRecord
    {
        $today = now()->toDateString();

        // work_date is an Eloquent 'date' cast column, which serializes to a
        // full "Y-m-d H:i:s" string in the database — an exact-equality
        // where() against a "Y-m-d" string never matches, so lookups must
        // use whereDate() (see the equivalent Cashbook::entries() fix).
        $existing = AttendanceRecord::where('employee_id', $employee->id)->whereDate('work_date', $today)->first();

        if ($existing?->clock_in_at !== null) {
            throw ValidationException::withMessages(['clock_in' => __('This employee has already clocked in today.')]);
        }

        if ($existing) {
            $existing->update(['clock_in_at' => now(), 'status' => AttendanceStatus::Present]);

            return $existing;
        }

        return AttendanceRecord::create([
            'branch_id' => $employee->branch_id,
            'employee_id' => $employee->id,
            'work_date' => $today,
            'clock_in_at' => now(),
            'status' => AttendanceStatus::Present,
        ]);
    }
}
