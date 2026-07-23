<?php

declare(strict_types=1);

namespace App\Domain\HR\Actions;

use App\Domain\HR\Enums\LeaveRequestStatus;
use App\Domain\HR\Support\LeaveBalanceCalculator;
use App\Models\LeaveRequest;
use App\Models\User;
use Illuminate\Validation\ValidationException;

class ApproveLeaveRequestAction
{
    public function __construct(private readonly LeaveBalanceCalculator $balanceCalculator) {}

    public function handle(LeaveRequest $leaveRequest, User $approver): LeaveRequest
    {
        if ($leaveRequest->status !== LeaveRequestStatus::Pending) {
            throw ValidationException::withMessages(['status' => __('Only a pending leave request can be approved.')]);
        }

        // Re-check the balance at approval time — other requests may have been
        // approved between submission and now.
        $remaining = $this->balanceCalculator->remainingDays($leaveRequest->employee, $leaveRequest->leaveType, $leaveRequest->start_date->year);

        if ($leaveRequest->days_requested > $remaining) {
            throw ValidationException::withMessages(['status' => __('Approving this request would exceed the employee\'s remaining leave balance.')]);
        }

        $leaveRequest->update([
            'status' => LeaveRequestStatus::Approved,
            'approved_by_user_id' => $approver->id,
            'approved_at' => now(),
        ]);

        return $leaveRequest;
    }
}
