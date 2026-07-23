<?php

declare(strict_types=1);

namespace App\Domain\Reservation\Actions;

use App\Domain\Reservation\Enums\ReservationStatus;
use App\Models\Reservation;
use App\Models\ReservationRoom;
use App\Models\User;
use Illuminate\Validation\ValidationException;

class CancelReservationAction
{
    public function handle(Reservation $reservation, User $cancelledBy, ?string $reason = null): Reservation
    {
        if (! $reservation->status->isActive() || $reservation->status === ReservationStatus::CheckedIn) {
            throw ValidationException::withMessages([
                'status' => __('A reservation that is checked in, checked out, or already cancelled cannot be cancelled this way.'),
            ]);
        }

        $fromStatus = $reservation->status;
        $feeCents = $this->calculateCancellationFeeCents($reservation);

        $reservation->forceFill([
            'status' => ReservationStatus::Cancelled,
            'cancelled_at' => now(),
            'cancellation_fee_cents' => $feeCents,
        ])->save();

        $reservation->statusLogs()->create([
            'from_status' => $fromStatus->value,
            'to_status' => ReservationStatus::Cancelled->value,
            'changed_by_user_id' => $cancelledBy->id,
            'reason' => $reason,
        ]);

        return $reservation;
    }

    private function calculateCancellationFeeCents(Reservation $reservation): int
    {
        $policy = $reservation->branch->cancellation_policy ?? [];
        $freeCancellationHours = (int) ($policy['free_cancellation_hours'] ?? 0);
        $feePercent = (int) ($policy['fee_percent'] ?? 0);

        $hoursUntilArrival = now()->diffInHours($reservation->arrival_date, false);

        if ($hoursUntilArrival >= $freeCancellationHours) {
            return 0;
        }

        $roomTotalCents = $reservation->rooms->sum(fn (ReservationRoom $room) => $room->rate_cents * $reservation->nights());

        return (int) round($roomTotalCents * $feePercent / 100);
    }
}
