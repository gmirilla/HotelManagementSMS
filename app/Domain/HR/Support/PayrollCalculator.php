<?php

declare(strict_types=1);

namespace App\Domain\HR\Support;

use App\Domain\HR\Enums\AttendanceStatus;
use App\Models\AttendanceRecord;
use App\Models\Employee;
use Illuminate\Support\Carbon;

/**
 * Pro-rates the employee's monthly base salary against the working days in
 * the payroll period, deducting one day's pay for each unexcused absence
 * recorded in attendance_records — the same ledger (never a manually typed
 * "days worked" figure) drives both the attendance board and the payslip.
 */
class PayrollCalculator
{
    /**
     * @return array{basic_cents: int, allowances_cents: int, deductions_cents: int, gross_cents: int, net_cents: int, days_present: int, days_absent: int, days_on_leave: int}
     */
    public function calculateForEmployee(Employee $employee, Carbon $periodStart, Carbon $periodEnd): array
    {
        $records = AttendanceRecord::where('employee_id', $employee->id)
            ->whereBetween('work_date', [$periodStart->toDateString(), $periodEnd->toDateString()])
            ->get();

        $daysPresent = $records->whereIn('status', [AttendanceStatus::Present, AttendanceStatus::Late, AttendanceStatus::HalfDay])->count();
        $daysAbsent = $records->where('status', AttendanceStatus::Absent)->count();
        $daysOnLeave = $records->where('status', AttendanceStatus::OnLeave)->count();

        $workingDays = $this->workingDaysBetween($periodStart, $periodEnd);
        $perDayCents = $workingDays > 0 ? intdiv($employee->base_salary_cents, $workingDays) : 0;

        $basicCents = $employee->base_salary_cents;
        $deductionsCents = $perDayCents * $daysAbsent;
        $grossCents = $basicCents;
        $netCents = max(0, $grossCents - $deductionsCents);

        return [
            'basic_cents' => $basicCents,
            'allowances_cents' => 0,
            'deductions_cents' => $deductionsCents,
            'gross_cents' => $grossCents,
            'net_cents' => $netCents,
            'days_present' => $daysPresent,
            'days_absent' => $daysAbsent,
            'days_on_leave' => $daysOnLeave,
        ];
    }

    private function workingDaysBetween(Carbon $start, Carbon $end): int
    {
        $workingDays = 0;
        $cursor = $start->copy();

        while ($cursor->lte($end)) {
            if (! $cursor->isWeekend()) {
                $workingDays++;
            }

            $cursor->addDay();
        }

        return $workingDays;
    }
}
