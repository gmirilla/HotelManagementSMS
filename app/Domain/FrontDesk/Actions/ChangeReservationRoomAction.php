<?php

declare(strict_types=1);

namespace App\Domain\FrontDesk\Actions;

use App\Domain\Accounting\Support\FolioLedgerPoster;
use App\Domain\FrontDesk\Enums\ChargeType;
use App\Domain\FrontDesk\Support\FolioBalanceCalculator;
use App\Domain\Reservation\Enums\ReservationStatus;
use App\Domain\Room\Enums\HousekeepingStatus;
use App\Domain\Room\Enums\RoomStatus;
use App\Domain\Room\Support\RateResolver;
use App\Models\Reservation;
use App\Models\ReservationRoom;
use App\Models\Room;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Moves a reservation onto a different physical room — an upgrade, a
 * downgrade, or a same-type reassignment (e.g. a maintenance issue) — either
 * before or after check-in. One Action covers both moments because the
 * underlying operation is the same: repoint reservation_rooms at the new
 * room/type.
 *
 * Before check-in, no physical room is occupied yet (CheckInGuestAction is
 * what actually flips a room to Occupied and opens the folio), so this just
 * rebooks the room/type — there's nothing to vacate and no folio to adjust.
 * After check-in it also frees the old room, occupies the new one, and — if
 * the room type changed — reverses the not-yet-consumed nights' Room charges
 * at the old rate and reposts them at the new one. Already-posted charges
 * are never edited in place (see FolioBalanceCalculator): the folio stays an
 * append-only ledger, so the guest's bill still shows exactly what happened
 * and when.
 */
class ChangeReservationRoomAction
{
    public function __construct(
        private readonly RateResolver $rateResolver,
        private readonly FolioBalanceCalculator $balanceCalculator,
        private readonly FolioLedgerPoster $ledgerPoster,
    ) {}

    public function handle(Reservation $reservation, Room $newRoom, User $staff, ?string $reason = null): ReservationRoom
    {
        if (! in_array($reservation->status, [ReservationStatus::Pending, ReservationStatus::Confirmed, ReservationStatus::CheckedIn], true)) {
            throw ValidationException::withMessages([
                'status' => __('Only a pending, confirmed, or checked-in reservation can change rooms.'),
            ]);
        }

        $reservationRoom = $reservation->rooms->first();

        if (! $reservationRoom) {
            throw ValidationException::withMessages([
                'room_id' => __('This reservation has no room booking to change.'),
            ]);
        }

        if ($reservationRoom->room_id === $newRoom->id) {
            throw ValidationException::withMessages([
                'room_id' => __('The guest is already assigned to this room.'),
            ]);
        }

        if (! $newRoom->isBookable()) {
            throw ValidationException::withMessages([
                'room_id' => __('The selected room is not currently available.'),
            ]);
        }

        $isCheckedIn = $reservation->status === ReservationStatus::CheckedIn;
        $roomTypeChanged = $reservationRoom->room_type_id !== $newRoom->room_type_id;
        $oldRoom = $reservationRoom->room;

        return DB::transaction(function () use ($reservation, $newRoom, $staff, $reason, $reservationRoom, $isCheckedIn, $roomTypeChanged, $oldRoom) {
            if ($isCheckedIn && $oldRoom) {
                $fromStatus = $oldRoom->status;
                $oldRoom->forceFill(['status' => RoomStatus::VacantDirty, 'housekeeping_status' => HousekeepingStatus::Dirty])->save();
                $oldRoom->statusLogs()->create([
                    'from_status' => $fromStatus->value,
                    'to_status' => RoomStatus::VacantDirty->value,
                    'changed_by_user_id' => $staff->id,
                    'reason' => $reason ?? 'Room change — vacated',
                ]);
            }

            if ($isCheckedIn) {
                $fromStatus = $newRoom->status;
                $newRoom->forceFill(['status' => RoomStatus::Occupied])->save();
                $newRoom->statusLogs()->create([
                    'from_status' => $fromStatus->value,
                    'to_status' => RoomStatus::Occupied->value,
                    'changed_by_user_id' => $staff->id,
                    'reason' => $reason ?? 'Room change — occupied',
                ]);
            }

            $averageRateCents = $this->averageRateCents($newRoom, $reservation);

            $reservationRoom->update([
                'room_id' => $newRoom->id,
                'room_type_id' => $newRoom->room_type_id,
                'rate_cents' => $averageRateCents,
            ]);

            if ($isCheckedIn && $roomTypeChanged) {
                $this->repriceRemainingNights($reservation, $newRoom, $staff);
            }

            return $reservationRoom->fresh();
        });
    }

    private function averageRateCents(Room $room, Reservation $reservation): int
    {
        $nightlyRates = $this->rateResolver->nightlyRatesForStay($room->roomType, $reservation->arrival_date, $reservation->departure_date);

        return $nightlyRates === [] ? 0 : (int) round(array_sum($nightlyRates) / count($nightlyRates));
    }

    private function repriceRemainingNights(Reservation $reservation, Room $newRoom, User $staff): void
    {
        $folio = $reservation->folio;

        if (! $folio || ! $folio->isOpen()) {
            return;
        }

        $today = now()->startOfDay();

        if ($today->gte($reservation->departure_date)) {
            return;
        }

        $reversedCents = (int) $folio->charges()
            ->where('charge_type', ChargeType::Room->value)
            ->whereDate('charge_date', '>=', $today->toDateString())
            ->sum('amount_cents');

        if ($reversedCents !== 0) {
            $reversal = $folio->charges()->create([
                'charge_type' => ChargeType::Room->value,
                'description' => __('Room change — reversing remaining nights at previous rate'),
                'amount_cents' => -$reversedCents,
                'charge_date' => $today->toDateString(),
                'posted_by_user_id' => $staff->id,
            ]);
            $reversal->setRelation('folio', $folio);
            $this->ledgerPoster->postCharge($reversal, $staff);
        }

        $nightlyRates = $this->rateResolver->nightlyRatesForStay($newRoom->roomType, $today, $reservation->departure_date);

        foreach ($nightlyRates as $date => $rateCents) {
            $charge = $folio->charges()->create([
                'charge_type' => ChargeType::Room->value,
                'description' => "Room {$newRoom->room_number} — {$date}",
                'amount_cents' => $rateCents,
                'charge_date' => $date,
                'posted_by_user_id' => $staff->id,
            ]);
            $charge->setRelation('folio', $folio);
            $this->ledgerPoster->postCharge($charge, $staff);
        }

        $this->balanceCalculator->recalculate($folio);
    }
}
