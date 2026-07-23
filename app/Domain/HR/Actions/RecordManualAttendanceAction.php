<?php

declare(strict_types=1);

namespace App\Domain\HR\Actions;

use App\Domain\HR\Enums\AttendanceStatus;
use App\Models\AttendanceRecord;
use App\Models\Employee;
use Illuminate\Support\Carbon;

class RecordManualAttendanceAction
{
    public function handle(Employee $employee, Carbon $workDate, AttendanceStatus $status, ?string $notes = null): AttendanceRecord
    {
        // work_date is an Eloquent 'date' cast column, which serializes to a
        // full "Y-m-d H:i:s" string in the database — updateOrCreate()'s
        // exact-equality lookup against a "Y-m-d" string never matches an
        // existing row, so the match must be done via whereDate() first.
        $record = AttendanceRecord::where('employee_id', $employee->id)->whereDate('work_date', $workDate->toDateString())->first();

        if ($record) {
            $record->update(['status' => $status, 'notes' => $notes]);

            return $record;
        }

        return AttendanceRecord::create([
            'branch_id' => $employee->branch_id,
            'employee_id' => $employee->id,
            'work_date' => $workDate->toDateString(),
            'status' => $status,
            'notes' => $notes,
        ]);
    }
}
