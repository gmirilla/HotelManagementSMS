<?php

declare(strict_types=1);

namespace App\Domain\FrontDesk\Actions;

use App\Domain\FrontDesk\Enums\FolioStatus;
use App\Domain\Reservation\Enums\ReservationStatus;
use App\Domain\Room\Enums\HousekeepingStatus;
use App\Domain\Room\Enums\RoomStatus;
use App\Models\Reservation;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CheckOutGuestAction
{
    public function handle(Reservation $reservation, User $staff, bool $force = false): Reservation
    {
        if ($reservation->status !== ReservationStatus::CheckedIn) {
            throw ValidationException::withMessages([
                'status' => __('Only a checked-in reservation can be checked out.'),
            ]);
        }

        $folio = $reservation->folio;

        if ($folio && $folio->balance_cents > 0 && ! $force) {
            throw ValidationException::withMessages([
                'balance' => __('The folio has an outstanding balance of :amount that must be settled before check-out.', [
                    'amount' => number_format($folio->balance_cents / 100, 2),
                ]),
            ]);
        }

        return DB::transaction(function () use ($reservation, $staff, $folio) {
            if ($folio) {
                $folio->forceFill(['status' => FolioStatus::Closed, 'closed_at' => now()])->save();
            }

            $room = $reservation->rooms->first()?->room;

            if ($room) {
                $fromRoomStatus = $room->status;
                $room->forceFill(['status' => RoomStatus::VacantDirty, 'housekeeping_status' => HousekeepingStatus::Dirty])->save();
                $room->statusLogs()->create([
                    'from_status' => $fromRoomStatus->value,
                    'to_status' => RoomStatus::VacantDirty->value,
                    'changed_by_user_id' => $staff->id,
                    'reason' => 'Guest check-out',
                ]);
            }

            $fromReservationStatus = $reservation->status;
            $reservation->forceFill(['status' => ReservationStatus::CheckedOut])->save();
            $reservation->statusLogs()->create([
                'from_status' => $fromReservationStatus->value,
                'to_status' => ReservationStatus::CheckedOut->value,
                'changed_by_user_id' => $staff->id,
                'reason' => 'Guest check-out',
            ]);

            return $reservation;
        });
    }
}
