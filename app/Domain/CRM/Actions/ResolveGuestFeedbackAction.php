<?php

declare(strict_types=1);

namespace App\Domain\CRM\Actions;

use App\Domain\CRM\Enums\FeedbackStatus;
use App\Models\GuestFeedback;
use Illuminate\Validation\ValidationException;

class ResolveGuestFeedbackAction
{
    public function handle(GuestFeedback $feedback, string $resolutionNotes): GuestFeedback
    {
        if ($feedback->status === FeedbackStatus::Closed) {
            throw ValidationException::withMessages(['status' => __('This feedback record is already closed.')]);
        }

        $feedback->update([
            'status' => FeedbackStatus::Resolved,
            'resolution_notes' => $resolutionNotes,
            'resolved_at' => now(),
        ]);

        return $feedback;
    }
}
