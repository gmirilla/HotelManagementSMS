<?php

declare(strict_types=1);

namespace App\Livewire\Reservations;

use App\Domain\Reservation\Actions\CancelReservationAction;
use App\Models\Reservation;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('components.layouts.app')]
#[Title('Reservation')]
class ReservationShow extends Component
{
    public Reservation $reservation;

    public string $cancelReason = '';

    public bool $showCancelForm = false;

    public function mount(Reservation $reservation): void
    {
        $this->authorize('view', $reservation);
        $this->reservation = $reservation;
    }

    public function cancel(CancelReservationAction $cancelReservation): void
    {
        $this->authorize('cancel', $this->reservation);

        $cancelReservation->handle($this->reservation, auth()->user(), $this->cancelReason ?: null);

        $this->reservation->refresh();
        $this->showCancelForm = false;
    }

    public function render()
    {
        $this->reservation->load(['guest', 'branch', 'rooms.roomType', 'rooms.room', 'statusLogs.changedBy', 'folio.charges', 'folio.payments']);

        return view('livewire.reservations.reservation-show');
    }
}
