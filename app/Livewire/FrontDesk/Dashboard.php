<?php

declare(strict_types=1);

namespace App\Livewire\FrontDesk;

use App\Domain\FrontDesk\Actions\ChangeReservationRoomAction;
use App\Domain\FrontDesk\Actions\CheckInGuestAction;
use App\Domain\FrontDesk\Actions\CheckOutGuestAction;
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

    public ?string $checkoutError = null;

    public ?int $changingRoomReservationId = null;

    public ?int $selectedNewRoomId = null;

    public string $roomChangeReason = '';

    #[Computed]
    public function arrivals(): Collection
    {
        return Reservation::where('branch_id', $this->branchId)
            ->whereIn('status', [ReservationStatus::Pending, ReservationStatus::Confirmed])
            ->whereDate('arrival_date', now()->toDateString())
            ->with(['guest', 'rooms.roomType'])
            ->get();
    }

    #[Computed]
    public function departures(): Collection
    {
        return Reservation::where('branch_id', $this->branchId)
            ->where('status', ReservationStatus::CheckedIn)
            ->whereDate('departure_date', '<=', now()->toDateString())
            ->with(['guest', 'folio', 'rooms.room'])
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
    }

    public function completeCheckIn(CheckInGuestAction $checkInGuest, ChangeReservationRoomAction $changeRoom): void
    {
        $this->validate(['selectedRoomId' => ['required', 'integer']]);

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

        $checkInGuest->handle($reservation, $room, auth()->user());

        $this->checkingInReservationId = null;
        $this->selectedRoomId = null;
        unset($this->arrivals, $this->inHouse);
    }

    public function checkOut(int $reservationId, CheckOutGuestAction $checkOutGuest): void
    {
        $reservation = Reservation::findOrFail($reservationId);
        $this->authorize('update', $reservation);

        try {
            $checkOutGuest->handle($reservation, auth()->user());
            $this->checkoutError = null;
        } catch (ValidationException $exception) {
            $this->checkoutError = collect($exception->errors())->flatten()->first();
        }

        unset($this->departures, $this->inHouse);
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
        return view('livewire.front-desk.dashboard');
    }
}
