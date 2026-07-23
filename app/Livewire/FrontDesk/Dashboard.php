<?php

declare(strict_types=1);

namespace App\Livewire\FrontDesk;

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
            ->with(['guest', 'rooms.room'])
            ->get();
    }

    #[Computed]
    public function availableRoomsForCheckIn(): Collection
    {
        if (! $this->checkingInReservationId) {
            return new Collection;
        }

        $reservation = Reservation::with('rooms')->find($this->checkingInReservationId);
        $roomTypeId = $reservation?->rooms->first()?->room_type_id;

        if (! $roomTypeId) {
            return new Collection;
        }

        return Room::where('branch_id', $this->branchId)
            ->where('room_type_id', $roomTypeId)
            ->whereIn('status', ['vacant_clean', 'vacant_dirty'])
            ->where('is_active', true)
            ->orderBy('room_number')
            ->get();
    }

    public function startCheckIn(int $reservationId): void
    {
        $this->checkingInReservationId = $reservationId;
        $this->selectedRoomId = null;
    }

    public function completeCheckIn(CheckInGuestAction $checkInGuest): void
    {
        $this->validate(['selectedRoomId' => ['required', 'integer']]);

        $reservation = Reservation::findOrFail($this->checkingInReservationId);
        $this->authorize('update', $reservation);

        $room = Room::findOrFail($this->selectedRoomId);

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

    public function render()
    {
        return view('livewire.front-desk.dashboard');
    }
}
