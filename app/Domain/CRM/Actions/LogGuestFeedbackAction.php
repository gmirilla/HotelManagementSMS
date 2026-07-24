<?php

declare(strict_types=1);

namespace App\Domain\CRM\Actions;

use App\Domain\CRM\Enums\FeedbackStatus;
use App\Domain\CRM\Enums\FeedbackType;
use App\Models\Branch;
use App\Models\Guest;
use App\Models\GuestFeedback;

class LogGuestFeedbackAction
{
    public function handle(Branch $branch, ?Guest $guest, FeedbackType $type, string $subject, string $description): GuestFeedback
    {
        return GuestFeedback::create([
            'branch_id' => $branch->id,
            'guest_id' => $guest?->id,
            'type' => $type,
            'subject' => $subject,
            'description' => $description,
            'status' => FeedbackStatus::Open,
        ]);
    }
}
