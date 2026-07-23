<?php

declare(strict_types=1);

namespace App\Domain\HR\Support;

use App\Domain\HR\Enums\LeaveRequestStatus;
use App\Models\Employee;
use App\Models\LeaveBalance;
use App\Models\LeaveRequest;
use App\Models\LeaveType;

/**
 * Days used are always derived from approved leave_requests rows, never
 * stored — the same ledger-truth pattern used for folio/inventory/account
 * balances elsewhere in this codebase.
 */
class LeaveBalanceCalculator
{
    public function usedDays(Employee $employee, LeaveType $leaveType, int $year): int
    {
        return (int) LeaveRequest::where('employee_id', $employee->id)
            ->where('leave_type_id', $leaveType->id)
            ->where('status', LeaveRequestStatus::Approved)
            ->whereYear('start_date', $year)
            ->sum('days_requested');
    }

    public function entitledDays(Employee $employee, LeaveType $leaveType, int $year): int
    {
        $balance = LeaveBalance::where('employee_id', $employee->id)
            ->where('leave_type_id', $leaveType->id)
            ->where('year', $year)
            ->first();

        return $balance?->entitled_days ?? $leaveType->days_per_year;
    }

    public function remainingDays(Employee $employee, LeaveType $leaveType, int $year): int
    {
        return max(0, $this->entitledDays($employee, $leaveType, $year) - $this->usedDays($employee, $leaveType, $year));
    }
}
