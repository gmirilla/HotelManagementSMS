<?php

declare(strict_types=1);

namespace App\Domain\HR\Actions;

use App\Domain\HR\Enums\LeaveRequestStatus;
use App\Domain\HR\Support\LeaveBalanceCalculator;
use App\Models\Employee;
use App\Models\LeaveRequest;
use App\Models\LeaveType;
use Illuminate\Support\Carbon;
use Illuminate\Validation\ValidationException;

class SubmitLeaveRequestAction
{
    public function __construct(private readonly LeaveBalanceCalculator $balanceCalculator) {}

    public function handle(Employee $employee, LeaveType $leaveType, Carbon $startDate, Carbon $endDate, ?string $reason = null): LeaveRequest
    {
        if ($endDate->lt($startDate)) {
            throw ValidationException::withMessages(['end_date' => __('The end date cannot be before the start date.')]);
        }

        $daysRequested = $startDate->diffInDays($endDate) + 1;

        $remaining = $this->balanceCalculator->remainingDays($employee, $leaveType, $startDate->year);

        if ($daysRequested > $remaining) {
            throw ValidationException::withMessages([
                'days_requested' => __('Only :remaining day(s) of :type leave remain for :year.', [
                    'remaining' => $remaining,
                    'type' => $leaveType->name,
                    'year' => $startDate->year,
                ]),
            ]);
        }

        return LeaveRequest::create([
            'employee_id' => $employee->id,
            'leave_type_id' => $leaveType->id,
            'start_date' => $startDate,
            'end_date' => $endDate,
            'days_requested' => $daysRequested,
            'reason' => $reason,
            'status' => LeaveRequestStatus::Pending,
        ]);
    }
}
