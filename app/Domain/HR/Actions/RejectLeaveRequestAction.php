<?php

declare(strict_types=1);

namespace App\Domain\HR\Actions;

use App\Domain\HR\Enums\LeaveRequestStatus;
use App\Models\LeaveRequest;
use App\Models\User;
use Illuminate\Validation\ValidationException;

class RejectLeaveRequestAction
{
    public function handle(LeaveRequest $leaveRequest, User $rejectedBy, string $rejectionReason): LeaveRequest
    {
        if ($leaveRequest->status !== LeaveRequestStatus::Pending) {
            throw ValidationException::withMessages(['status' => __('Only a pending leave request can be rejected.')]);
        }

        $leaveRequest->update([
            'status' => LeaveRequestStatus::Rejected,
            'approved_by_user_id' => $rejectedBy->id,
            'approved_at' => now(),
            'rejection_reason' => $rejectionReason,
        ]);

        return $leaveRequest;
    }
}
