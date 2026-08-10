<?php

declare(strict_types=1);

namespace App\Livewire\FrontDesk;

use App\Domain\FrontDesk\Actions\ChangeReservationRoomAction;
use App\Domain\FrontDesk\Actions\CheckInGuestAction;
use App\Domain\FrontDesk\Actions\CheckOutGuestAction;
use App\Domain\FrontDesk\Actions\PostFolioChargeAction;
use App\Domain\FrontDesk\Enums\ChargeType;
use App\Domain\Payment\Actions\RecordFolioPaymentAction;
use App\Domain\Payment\Enums\PaymentMethod;
use App\Domain\Reservation\Enums\ReservationStatus;
use App\Livewire\Concerns\InteractsWithActiveBranch;
use App\Models\Reservation;
use App\Models\Room;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('components.layouts.app')]
#[Title('Front Desk')]
class Dashboard extends Component
{
    use InteractsWithActiveBranch;

    public string $tab = 'arrivals';

    public ?int $checkingInReservationId = null;

    public ?int $selectedRoomId = null;

    public string $earlyCheckInFee = '';

    public ?string $checkoutError = null;

    public ?int $checkoutErrorReservationId = null;

    public ?int $lastCheckedOutFolioId = null;

    public ?int $forceCheckoutReservationId = null;

    public string $forceCheckoutReason = '';

    public ?int $payingReservationId = null;

    public string $paymentMethod = 'cash';

    public string $paymentAmount = '';

    public ?int $addingLateFeeReservationId = null;

    public string $lateFeeAmount = '';

    public ?int $changingRoomReservationId = null;

    public ?int $selectedNewRoomId = null;

    public string $roomChangeReason = '';

    #[Computed]
    public function arrivals(): Collection
    {
        return Reservation::where('branch_id', $this->branchId)
            ->whereIn('status', [ReservationStatus::Pending, ReservationStatus::Confirmed])
            ->whereDate('arrival_date', now()->toDateString())
            ->with(['guest', 'rooms.roomType', 'branch'])
            ->get();
    }

    #[Computed]
    public function departures(): Collection
    {
        return Reservation::where('branch_id', $this->branchId)
            ->where('status', ReservationStatus::CheckedIn)
            ->whereDate('departure_date', '<=', now()->toDateString())
            ->with(['guest', 'folio', 'rooms.room', 'branch'])
            ->get();
    }

    #[Computed]
    public function inHouse(): Collection
    {
        return Reservation::where('branch_id', $this->branchId)
            ->where('status', ReservationStatus::CheckedIn)
            ->with(['guest', 'rooms.room.roomType'])
            ->get();
    }

    /**
     * Every bookable room in the branch, not just the reservation's booked
     * type — the receptionist can upgrade or change the room type right here
     * as part of check-in, not only pick among rooms of what was booked.
     */
    #[Computed]
    public function availableRoomsForCheckIn(): Collection
    {
        if (! $this->checkingInReservationId) {
            return new Collection;
        }

        return $this->bookableRooms();
    }

    /**
     * @return Collection<int, Room>
     */
    #[Computed]
    public function availableRoomsForRoomChange(): Collection
    {
        if (! $this->changingRoomReservationId) {
            return new Collection;
        }

        $currentRoomId = Reservation::with('rooms')->find($this->changingRoomReservationId)?->rooms->first()?->room_id;

        return $this->bookableRooms($currentRoomId);
    }

    public function startCheckIn(int $reservationId): void
    {
        $this->checkingInReservationId = $reservationId;
        $this->selectedRoomId = null;
        $this->earlyCheckInFee = '';
    }

    public function completeCheckIn(CheckInGuestAction $checkInGuest, ChangeReservationRoomAction $changeRoom): void
    {
        $this->validate([
            'selectedRoomId' => ['required', 'integer'],
            'earlyCheckInFee' => ['nullable', 'numeric', 'min:0.01'],
        ]);

        $reservation = Reservation::with('rooms')->findOrFail($this->checkingInReservationId);
        $this->authorize('update', $reservation);

        $room = Room::findOrFail($this->selectedRoomId);
        abort_unless($room->branch_id === $this->branchId, 403);

        // The receptionist can hand over a different room type than what was
        // booked (an upgrade, a downgrade, or just a swap) right here at
        // check-in — reconcile the booking first, then check in as normal.
        if ($reservation->rooms->first()?->room_type_id !== $room->room_type_id) {
            $changeRoom->handle($reservation, $room, auth()->user(), 'Room type changed at check-in');
            $reservation->load('rooms');
        }

        $checkInGuest->handle(
            $reservation,
            $room,
            auth()->user(),
            earlyCheckInFeeCents: $this->earlyCheckInFee !== '' ? (int) round(((float) $this->earlyCheckInFee) * 100) : null,
        );

        $this->checkingInReservationId = null;
        $this->selectedRoomId = null;
        $this->earlyCheckInFee = '';
        unset($this->arrivals, $this->inHouse);
    }

    public function checkOut(int $reservationId, CheckOutGuestAction $checkOutGuest): void
    {
        $reservation = Reservation::with('folio')->findOrFail($reservationId);
        $this->authorize('update', $reservation);

        try {
            $checkOutGuest->handle($reservation, auth()->user());
            $this->checkoutError = null;
            $this->checkoutErrorReservationId = null;
            $this->lastCheckedOutFolioId = $reservation->folio?->id;
        } catch (ValidationException $exception) {
            $this->checkoutError = collect($exception->errors())->flatten()->first();
            $this->checkoutErrorReservationId = $reservationId;
        }

        unset($this->departures, $this->inHouse);
    }

    public function startForceCheckout(int $reservationId): void
    {
        $reservation = Reservation::with('folio')->findOrFail($reservationId);
        abort_if(! $reservation->folio, 404);
        $this->authorize('forceCheckout', $reservation->folio);

        $this->forceCheckoutReservationId = $reservationId;
        $this->forceCheckoutReason = '';
    }

    public function cancelForceCheckout(): void
    {
        $this->forceCheckoutReservationId = null;
        $this->forceCheckoutReason = '';
    }

    public function confirmForceCheckout(CheckOutGuestAction $checkOutGuest): void
    {
        $this->validate(['forceCheckoutReason' => ['required', 'string', 'max:500']]);

        $reservation = Reservation::with('folio')->findOrFail($this->forceCheckoutReservationId);
        abort_if(! $reservation->folio, 404);
        $this->authorize('forceCheckout', $reservation->folio);

        $checkOutGuest->handle($reservation, auth()->user(), force: true, forceReason: $this->forceCheckoutReason);

        $this->lastCheckedOutFolioId = $reservation->folio?->id;
        $this->checkoutError = null;
        $this->checkoutErrorReservationId = null;
        $this->cancelForceCheckout();
        unset($this->departures, $this->inHouse);
    }

    public function startPayment(int $reservationId): void
    {
        $reservation = Reservation::with('folio')->findOrFail($reservationId);
        abort_if(! $reservation->folio, 404);
        $this->authorize('update', $reservation->folio);

        $this->payingReservationId = $reservationId;
        $this->paymentMethod = 'cash';
        $this->paymentAmount = '';
    }

    public function cancelPayment(): void
    {
        $this->payingReservationId = null;
        $this->paymentAmount = '';
    }

    public function recordPayment(RecordFolioPaymentAction $recordPayment): void
    {
        $this->validate([
            'paymentMethod' => ['required', 'string'],
            'paymentAmount' => ['required', 'numeric', 'min:0.01'],
        ]);

        $reservation = Reservation::with('folio')->findOrFail($this->payingReservationId);
        abort_if(! $reservation->folio, 404);
        $this->authorize('update', $reservation->folio);

        $recordPayment->handle(
            $reservation->folio,
            $this->paymentMethod,
            (int) round(((float) $this->paymentAmount) * 100),
            auth()->user(),
        );

        $this->cancelPayment();
        unset($this->departures);
    }

    public function startLateFee(int $reservationId): void
    {
        $reservation = Reservation::with('folio')->findOrFail($reservationId);
        abort_if(! $reservation->folio, 404);
        $this->authorize('update', $reservation->folio);

        $this->addingLateFeeReservationId = $reservationId;
        $this->lateFeeAmount = '';
    }

    public function cancelLateFee(): void
    {
        $this->addingLateFeeReservationId = null;
        $this->lateFeeAmount = '';
    }

    public function confirmLateFee(PostFolioChargeAction $postCharge): void
    {
        $this->validate(['lateFeeAmount' => ['required', 'numeric', 'min:0.01']]);

        $reservation = Reservation::with('folio')->findOrFail($this->addingLateFeeReservationId);
        abort_if(! $reservation->folio, 404);
        $this->authorize('update', $reservation->folio);

        $postCharge->handle(
            $reservation->folio,
            ChargeType::LateCheckout->value,
            'Late checkout fee',
            (int) round(((float) $this->lateFeeAmount) * 100),
            auth()->user(),
        );

        $this->cancelLateFee();
        unset($this->departures);
    }

    public function startRoomChange(int $reservationId): void
    {
        $reservation = Reservation::findOrFail($reservationId);
        $this->authorize('update', $reservation);

        $this->changingRoomReservationId = $reservationId;
        $this->selectedNewRoomId = null;
        $this->roomChangeReason = '';
    }

    public function cancelRoomChange(): void
    {
        $this->changingRoomReservationId = null;
        $this->selectedNewRoomId = null;
        $this->roomChangeReason = '';
    }

    public function completeRoomChange(ChangeReservationRoomAction $changeRoom): void
    {
        $this->validate([
            'selectedNewRoomId' => ['required', 'integer'],
            'roomChangeReason' => ['nullable', 'string', 'max:255'],
        ]);

        $reservation = Reservation::with('rooms')->findOrFail($this->changingRoomReservationId);
        $this->authorize('update', $reservation);

        // Defense against a tampered room list: the selected room must
        // actually belong to this branch, regardless of what the (already
        // branch-scoped) picker displayed client-side.
        $room = Room::findOrFail($this->selectedNewRoomId);
        abort_unless($room->branch_id === $this->branchId, 403);

        $changeRoom->handle($reservation, $room, auth()->user(), $this->roomChangeReason ?: null);

        $this->cancelRoomChange();
        unset($this->inHouse);
    }

    /**
     * @return Collection<int, Room>
     */
    private function bookableRooms(?int $excludingRoomId = null): Collection
    {
        return Room::where('branch_id', $this->branchId)
            ->whereIn('status', ['vacant_clean', 'vacant_dirty'])
            ->where('is_active', true)
            ->when($excludingRoomId, fn ($query, $roomId) => $query->where('id', '!=', $roomId))
            ->with('roomType')
            ->orderBy('room_type_id')
            ->orderBy('room_number')
            ->get();
    }

    public function render()
    {
        return view('livewire.front-desk.dashboard', [
            'paymentMethods' => PaymentMethod::manual(),
        ]);
    }
}
