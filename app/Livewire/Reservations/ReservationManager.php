<?php

declare(strict_types=1);

namespace App\Livewire\Reservations;

use App\Domain\Reservation\Enums\ReservationStatus;
use App\Livewire\Concerns\InteractsWithActiveBranch;
use App\Models\Reservation;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('components.layouts.app')]
#[Title('Reservations')]
class ReservationManager extends Component
{
    use InteractsWithActiveBranch, WithPagination;

    #[Url]
    public string $statusFilter = '';

    #[Url]
    public string $search = '';

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function reservations(): LengthAwarePaginator
    {
        return Reservation::query()
            ->where('branch_id', $this->branchId)
            ->with(['guest', 'rooms.roomType'])
            ->when($this->statusFilter, fn ($query) => $query->where('status', $this->statusFilter))
            ->when($this->search, function ($query) {
                $query->where(function ($inner) {
                    $inner->where('confirmation_code', 'like', "%{$this->search}%")
                        ->orWhereHas('guest', fn ($g) => $g->where('first_name', 'like', "%{$this->search}%")
                            ->orWhere('last_name', 'like', "%{$this->search}%"));
                });
            })
            ->orderByDesc('arrival_date')
            ->paginate(15);
    }

    public function render()
    {
        return view('livewire.reservations.reservation-manager', [
            'reservations' => $this->reservations(),
            'statuses' => ReservationStatus::cases(),
        ]);
    }
}
