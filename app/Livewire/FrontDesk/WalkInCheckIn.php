<?php

declare(strict_types=1);

namespace App\Livewire\FrontDesk;

use App\Domain\FrontDesk\Actions\CheckInGuestAction;
use App\Domain\Guest\Actions\CreateGuestAction;
use App\Domain\Guest\Enums\DocumentType;
use App\Domain\Reservation\Actions\CreateReservationAction;
use App\Domain\Reservation\Enums\ReservationSource;
use App\Domain\Reservation\Support\AvailabilityChecker;
use App\Domain\Room\Support\RateResolver;
use App\Livewire\Concerns\InteractsWithActiveBranch;
use App\Models\Guest;
use App\Models\Reservation;
use App\Models\Room;
use App\Models\RoomType;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Carbon;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('components.layouts.app')]
#[Title('Walk-In Check-In')]
class WalkInCheckIn extends Component
{
    use InteractsWithActiveBranch;

    public int $step = 1;

    // Step 1 — stay details.
    public string $arrivalDate = '';

    public string $departureDate = '';

    public int $adults = 1;

    public int $children = 0;

    // Step 2 — room type.
    public ?int $selectedRoomTypeId = null;

    // Step 3 — physical room.
    public ?int $selectedRoomId = null;

    // Step 4 — guest.
    public string $guestSearch = '';

    public ?int $selectedGuestId = null;

    public bool $creatingNewGuest = false;

    public string $newGuestFirstName = '';

    public string $newGuestLastName = '';

    public string $newGuestEmail = '';

    public string $newGuestPhone = '';

    public string $newGuestNationality = '';

    // Step 4 — optional ID document, only captured for a newly created guest.
    public string $documentType = '';

    public string $documentNumber = '';

    public string $documentIssuingCountry = '';

    public string $documentExpiresOn = '';

    public string $specialRequests = '';

    public ?string $submitError = null;

    public function mount(): void
    {
        $this->authorize('create', Reservation::class);

        $this->arrivalDate = now()->toDateString();
        $this->departureDate = now()->addDay()->toDateString();
    }

    public function proceedToRoomType(): void
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
        $this->selectedRoomId = null;
        unset($this->availableRooms);
        $this->step = 3;
    }

    /**
     * @return Collection<int, Room>
     */
    #[Computed]
    public function availableRooms(): Collection
    {
        if (! $this->selectedRoomTypeId) {
            return new Collection;
        }

        return Room::where('branch_id', $this->branchId)
            ->where('room_type_id', $this->selectedRoomTypeId)
            ->whereIn('status', ['vacant_clean', 'vacant_dirty'])
            ->where('is_active', true)
            ->orderBy('room_number')
            ->get();
    }

    public function selectRoom(int $roomId): void
    {
        $room = Room::findOrFail($roomId);
        abort_unless($room->branch_id === $this->branchId, 403);
        abort_unless($room->room_type_id === $this->selectedRoomTypeId, 403);

        $this->selectedRoomId = $roomId;
        $this->step = 4;
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

    #[Computed]
    public function selectedGuest(): ?Guest
    {
        return $this->selectedGuestId ? Guest::find($this->selectedGuestId) : null;
    }

    public function confirmCheckIn(
        CreateReservationAction $createReservation,
        CheckInGuestAction $checkInGuest,
        CreateGuestAction $createGuest,
    ): void {
        $this->authorize('create', Reservation::class);
        $this->submitError = null;

        if ($this->creatingNewGuest) {
            $this->authorize('create', Guest::class);

            $this->validate([
                'newGuestFirstName' => ['required', 'string', 'max:255'],
                'newGuestLastName' => ['required', 'string', 'max:255'],
                'newGuestEmail' => ['nullable', 'email', 'max:255'],
                'newGuestPhone' => ['nullable', 'string', 'max:50'],
                'newGuestNationality' => ['nullable', 'string', 'max:100'],
                'documentType' => ['nullable', 'in:passport,national_id,visa,other', 'required_with:documentNumber'],
                'documentNumber' => ['nullable', 'string', 'max:255', 'required_with:documentType'],
                'documentIssuingCountry' => ['nullable', 'string', 'max:100'],
                'documentExpiresOn' => ['nullable', 'date'],
            ]);

            $guest = $createGuest->handle(
                tenantId: auth()->user()->tenant_id,
                firstName: $this->newGuestFirstName,
                lastName: $this->newGuestLastName,
                email: $this->newGuestEmail ?: null,
                phone: $this->newGuestPhone ?: null,
                nationality: $this->newGuestNationality ?: null,
                documentType: $this->documentType ? DocumentType::from($this->documentType) : null,
                documentNumber: $this->documentNumber ?: null,
                documentIssuingCountry: $this->documentIssuingCountry ?: null,
                documentExpiresOn: $this->documentExpiresOn ? Carbon::parse($this->documentExpiresOn) : null,
            );

            $this->selectedGuestId = $guest->id;
            // Prevents a resubmit (e.g. after the reservation/check-in step
            // below fails) from creating a second, duplicate guest record.
            $this->creatingNewGuest = false;
        }

        $this->validate([
            'selectedGuestId' => ['required', 'integer', 'exists:guests,id'],
            'selectedRoomTypeId' => ['required', 'integer'],
            'selectedRoomId' => ['required', 'integer'],
        ]);

        $roomType = RoomType::findOrFail($this->selectedRoomTypeId);
        abort_unless($roomType->branch_id === $this->branchId, 403);

        try {
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
        } catch (ValidationException $e) {
            $this->submitError = collect($e->errors())->flatten()->first();
            unset($this->availableRoomTypes);
            $this->step = 2;

            return;
        }

        // Deliberately not wrapped in one outer transaction with the action
        // above: if the chosen room was taken by another staff member in the
        // meantime, the reservation just created stays Confirmed — a normal,
        // recoverable state already surfaced on the Front Desk arrivals list,
        // where staff can finish the check-in instead of losing the booking.
        $room = Room::findOrFail($this->selectedRoomId);

        try {
            $folio = $checkInGuest->handle($reservation, $room, auth()->user());
        } catch (ValidationException $e) {
            $this->submitError = collect($e->errors())->flatten()->first()
                . ' The reservation (' . $reservation->confirmation_code . ') was created and remains confirmed — finish checking the guest in from the Front Desk arrivals list.';

            return;
        }

        $this->redirectRoute('folios.show', $folio, navigate: true);
    }

    public function render()
    {
        return view('livewire.front-desk.walk-in-check-in');
    }
}
