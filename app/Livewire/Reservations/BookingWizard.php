<?php

declare(strict_types=1);

namespace App\Livewire\Reservations;

use App\Domain\Guest\Enums\GuestType;
use App\Domain\Reservation\Actions\CreateReservationAction;
use App\Domain\Reservation\Enums\ReservationSource;
use App\Domain\Reservation\Support\AvailabilityChecker;
use App\Domain\Room\Support\RateResolver;
use App\Livewire\Concerns\InteractsWithActiveBranch;
use App\Models\Guest;
use App\Models\Reservation;
use App\Models\RoomType;
use Illuminate\Support\Carbon;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('components.layouts.app')]
#[Title('New Reservation')]
class BookingWizard extends Component
{
    use InteractsWithActiveBranch;

    public int $step = 1;

    public string $arrivalDate = '';

    public string $departureDate = '';

    public int $adults = 1;

    public int $children = 0;

    public ?int $selectedRoomTypeId = null;

    public string $guestSearch = '';

    public ?int $selectedGuestId = null;

    public bool $creatingNewGuest = false;

    public string $newGuestFirstName = '';

    public string $newGuestLastName = '';

    public string $newGuestEmail = '';

    public string $specialRequests = '';

    public function mount(): void
    {
        $this->arrivalDate = now()->addDay()->toDateString();
        $this->departureDate = now()->addDays(2)->toDateString();
    }

    public function searchAvailability(): void
    {
        $this->validate([
            'arrivalDate' => ['required', 'date', 'after_or_equal:today'],
            'departureDate' => ['required', 'date', 'after:arrivalDate'],
            'adults' => ['required', 'integer', 'min:1', 'max:20'],
            'children' => ['required', 'integer', 'min:0', 'max:20'],
        ]);

        $this->step = 2;
    }

    #[Computed]
    public function availableRoomTypes()
    {
        $checker = app(AvailabilityChecker::class);
        $rateResolver = app(RateResolver::class);

        $arrival = Carbon::parse($this->arrivalDate);
        $departure = Carbon::parse($this->departureDate);

        return RoomType::where('branch_id', $this->branchId)
            ->where('is_active', true)
            ->with('rates')
            ->get()
            ->map(function (RoomType $roomType) use ($checker, $rateResolver, $arrival, $departure) {
                $rates = $rateResolver->nightlyRatesForStay($roomType, $arrival, $departure);

                return [
                    'roomType' => $roomType,
                    'available' => $checker->availableRoomCount($roomType, $arrival, $departure),
                    'averageRateCents' => (int) round(array_sum($rates) / max(1, count($rates))),
                ];
            })
            ->filter(fn (array $row) => $row['available'] > 0);
    }

    public function selectRoomType(int $roomTypeId): void
    {
        $this->selectedRoomTypeId = $roomTypeId;
        $this->step = 3;
    }

    #[Computed]
    public function guestResults()
    {
        if (mb_strlen($this->guestSearch) < 2) {
            return collect();
        }

        return Guest::where('tenant_id', auth()->user()->tenant_id)
            ->where(function ($query) {
                $query->where('first_name', 'like', "%{$this->guestSearch}%")
                    ->orWhere('last_name', 'like', "%{$this->guestSearch}%")
                    ->orWhere('email', 'like', "%{$this->guestSearch}%");
            })
            ->limit(10)
            ->get();
    }

    public function selectGuest(int $guestId): void
    {
        $this->selectedGuestId = $guestId;
        $this->creatingNewGuest = false;
    }

    public function confirm(CreateReservationAction $createReservation): void
    {
        $this->authorize('create', Reservation::class);

        if ($this->creatingNewGuest) {
            $this->validate([
                'newGuestFirstName' => ['required', 'string', 'max:255'],
                'newGuestLastName' => ['required', 'string', 'max:255'],
                'newGuestEmail' => ['nullable', 'email', 'max:255'],
            ]);

            $guest = Guest::create([
                'tenant_id' => auth()->user()->tenant_id,
                'first_name' => $this->newGuestFirstName,
                'last_name' => $this->newGuestLastName,
                'email' => $this->newGuestEmail ?: null,
                'guest_type' => GuestType::Individual,
            ]);

            $this->selectedGuestId = $guest->id;
        }

        $this->validate(['selectedGuestId' => ['required', 'integer', 'exists:guests,id']]);

        // The room type picker only lists this branch's room types, but the
        // property itself is client-mutable — re-verify server-side that a
        // different branch's room type (with different rates) can't be
        // booked against this reservation's branch.
        $roomType = RoomType::findOrFail($this->selectedRoomTypeId);
        abort_unless($roomType->branch_id === $this->branchId, 403);

        $reservation = $createReservation->handle(
            branchId: $this->branchId,
            guestId: $this->selectedGuestId,
            roomType: $roomType,
            arrival: Carbon::parse($this->arrivalDate),
            departure: Carbon::parse($this->departureDate),
            adults: $this->adults,
            children: $this->children,
            source: ReservationSource::WalkIn->value,
            specialRequests: $this->specialRequests ?: null,
        );

        $this->redirectRoute('reservations.show', $reservation, navigate: true);
    }

    public function render()
    {
        return view('livewire.reservations.booking-wizard');
    }
}
