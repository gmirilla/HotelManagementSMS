<?php

declare(strict_types=1);

namespace App\Domain\FrontDesk\Actions;

use App\Domain\Accounting\Support\FolioLedgerPoster;
use App\Domain\FrontDesk\Enums\ChargeType;
use App\Domain\FrontDesk\Enums\FolioStatus;
use App\Domain\FrontDesk\Support\FolioBalanceCalculator;
use App\Domain\Reservation\Enums\ReservationStatus;
use App\Domain\Room\Enums\RoomStatus;
use App\Domain\Room\Support\RateResolver;
use App\Models\Folio;
use App\Models\Reservation;
use App\Models\Room;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Checks a guest in: assigns the physical room, opens a folio pre-charged for
 * the full stay (a simplification — a nightly "night audit" job that posts
 * one night at a time is the more typical PMS approach and is a natural
 * follow-up once the scheduler-driven jobs from system-architecture.md §7
 * are built), and moves the room and reservation into their occupied states.
 */
class CheckInGuestAction
{
    public function __construct(
        private readonly RateResolver $rateResolver,
        private readonly FolioBalanceCalculator $balanceCalculator,
        private readonly FolioLedgerPoster $ledgerPoster,
    ) {}

    public function handle(Reservation $reservation, Room $room, User $staff): Folio
    {
        if (! in_array($reservation->status, [ReservationStatus::Pending, ReservationStatus::Confirmed], true)) {
            throw ValidationException::withMessages([
                'status' => __('Only pending or confirmed reservations can be checked in.'),
            ]);
        }

        $reservationRoom = $reservation->rooms->first();

        if (! $reservationRoom || $reservationRoom->room_type_id !== $room->room_type_id) {
            throw ValidationException::withMessages([
                'room_id' => __('The selected room does not match the room type booked.'),
            ]);
        }

        if (! $room->isBookable()) {
            throw ValidationException::withMessages([
                'room_id' => __('The selected room is not currently available for check-in.'),
            ]);
        }

        return DB::transaction(function () use ($reservation, $room, $staff, $reservationRoom) {
            $fromRoomStatus = $room->status;
            $room->forceFill(['status' => RoomStatus::Occupied])->save();
            $room->statusLogs()->create([
                'from_status' => $fromRoomStatus->value,
                'to_status' => RoomStatus::Occupied->value,
                'changed_by_user_id' => $staff->id,
                'reason' => 'Guest check-in',
            ]);

            $reservationRoom->update(['room_id' => $room->id]);

            $fromReservationStatus = $reservation->status;
            $reservation->forceFill(['status' => ReservationStatus::CheckedIn])->save();
            $reservation->statusLogs()->create([
                'from_status' => $fromReservationStatus->value,
                'to_status' => ReservationStatus::CheckedIn->value,
                'changed_by_user_id' => $staff->id,
                'reason' => 'Guest check-in',
            ]);

            $folio = Folio::create([
                'branch_id' => $reservation->branch_id,
                'reservation_id' => $reservation->id,
                'guest_id' => $reservation->guest_id,
                'status' => FolioStatus::Open,
            ]);

            $nightlyRates = $this->rateResolver->nightlyRatesForStay($room->roomType, $reservation->arrival_date, $reservation->departure_date);

            foreach ($nightlyRates as $date => $rateCents) {
                $charge = $folio->charges()->create([
                    'charge_type' => ChargeType::Room,
                    'description' => "Room {$room->room_number} — {$date}",
                    'amount_cents' => $rateCents,
                    'charge_date' => $date,
                    'posted_by_user_id' => $staff->id,
                ]);
                $charge->setRelation('folio', $folio);
                $this->ledgerPoster->postCharge($charge, $staff);
            }

            $this->balanceCalculator->recalculate($folio);

            return $folio;
        });
    }
}
