<?php

declare(strict_types=1);

namespace App\Domain\Event\Actions;

use App\Domain\Event\Enums\EventBookingStatus;
use App\Models\EventBooking;
use Illuminate\Validation\ValidationException;

class ConfirmEventBookingAction
{
    public function handle(EventBooking $booking): EventBooking
    {
        if ($booking->status !== EventBookingStatus::Tentative) {
            throw ValidationException::withMessages(['status' => __('Only a tentative booking can be confirmed.')]);
        }

        $booking->update(['status' => EventBookingStatus::Confirmed]);

        return $booking;
    }
}
