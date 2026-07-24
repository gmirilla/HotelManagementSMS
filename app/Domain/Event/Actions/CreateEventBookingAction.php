<?php

declare(strict_types=1);

namespace App\Domain\Event\Actions;

use App\Domain\Event\Enums\EventBookingStatus;
use App\Models\Branch;
use App\Models\CorporateAccount;
use App\Models\EventBooking;
use App\Models\EventSpace;
use App\Models\Guest;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Validation\ValidationException;

class CreateEventBookingAction
{
    public function handle(
        Branch $branch,
        EventSpace $eventSpace,
        string $title,
        string $eventType,
        Carbon $startAt,
        Carbon $endAt,
        ?Guest $guest = null,
        ?CorporateAccount $corporateAccount = null,
        ?int $attendeeCount = null,
        ?string $notes = null,
        ?User $createdBy = null,
    ): EventBooking {
        if ($endAt->lte($startAt)) {
            throw ValidationException::withMessages(['end_at' => __('The event must end after it starts.')]);
        }

        $overlaps = EventBooking::where('event_space_id', $eventSpace->id)
            ->whereIn('status', [EventBookingStatus::Tentative, EventBookingStatus::Confirmed])
            ->where('start_at', '<', $endAt)
            ->where('end_at', '>', $startAt)
            ->exists();

        if ($overlaps) {
            throw ValidationException::withMessages(['event_space_id' => __('This event space is already booked for the selected time window.')]);
        }

        return EventBooking::create([
            'branch_id' => $branch->id,
            'event_space_id' => $eventSpace->id,
            'guest_id' => $guest?->id,
            'corporate_account_id' => $corporateAccount?->id,
            'title' => $title,
            'event_type' => $eventType,
            'start_at' => $startAt,
            'end_at' => $endAt,
            'attendee_count' => $attendeeCount,
            'status' => EventBookingStatus::Tentative,
            'notes' => $notes,
            'created_by_user_id' => $createdBy?->id,
        ]);
    }
}
