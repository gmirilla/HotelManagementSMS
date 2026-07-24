<?php

declare(strict_types=1);

namespace App\Domain\CRM\Actions;

use App\Domain\CRM\Enums\FeedbackStatus;
use App\Models\GuestFeedback;
use App\Models\User;
use Illuminate\Validation\ValidationException;

class AssignGuestFeedbackAction
{
    public function handle(GuestFeedback $feedback, User $assignee): GuestFeedback
    {
        if ($feedback->status === FeedbackStatus::Closed) {
            throw ValidationException::withMessages(['status' => __('A closed feedback record cannot be reassigned.')]);
        }

        $feedback->update([
            'assigned_to_user_id' => $assignee->id,
            'status' => FeedbackStatus::InProgress,
        ]);

        return $feedback;
    }
}
