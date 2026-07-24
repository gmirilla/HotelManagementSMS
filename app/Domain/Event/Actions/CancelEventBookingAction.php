<?php

declare(strict_types=1);

namespace App\Domain\Event\Actions;

use App\Domain\Event\Enums\EventBookingStatus;
use App\Models\EventBooking;
use Illuminate\Validation\ValidationException;

class CancelEventBookingAction
{
    public function handle(EventBooking $booking): EventBooking
    {
        if ($booking->status === EventBookingStatus::Completed) {
            throw ValidationException::withMessages(['status' => __('A completed booking cannot be cancelled.')]);
        }

        if ($booking->status === EventBookingStatus::Cancelled) {
            throw ValidationException::withMessages(['status' => __('This booking is already cancelled.')]);
        }

        $booking->update(['status' => EventBookingStatus::Cancelled]);

        return $booking;
    }
}
