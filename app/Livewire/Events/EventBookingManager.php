<?php

declare(strict_types=1);

namespace App\Livewire\Events;

use App\Domain\Event\Actions\AddEventBookingItemAction;
use App\Domain\Event\Actions\CancelEventBookingAction;
use App\Domain\Event\Actions\ConfirmEventBookingAction;
use App\Domain\Event\Actions\CreateEventBookingAction;
use App\Domain\Event\Support\EventBillCalculator;
use App\Livewire\Concerns\InteractsWithActiveBranch;
use App\Models\Branch;
use App\Models\EventBooking;
use App\Models\EventService;
use App\Models\EventSpace;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Carbon;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('components.layouts.app')]
#[Title('Event Bookings')]
class EventBookingManager extends Component
{
    use InteractsWithActiveBranch;

    public bool $showForm = false;

    public string $eventSpaceId = '';

    public string $title = '';

    public string $eventType = 'conference';

    public string $startAt = '';

    public string $endAt = '';

    public string $attendeeCount = '';

    public ?int $selectedBookingId = null;

    public string $selectedServiceId = '';

    public string $itemQuantity = '1';

    #[Computed]
    public function bookings(): Collection
    {
        return EventBooking::where('branch_id', $this->branchId)
            ->with('eventSpace')
            ->orderByDesc('start_at')
            ->get();
    }

    #[Computed]
    public function spaces(): Collection
    {
        return EventSpace::where('branch_id', $this->branchId)->where('is_active', true)->orderBy('name')->get();
    }

    #[Computed]
    public function services(): Collection
    {
        return EventService::where('branch_id', $this->branchId)->where('is_active', true)->orderBy('name')->get();
    }

    #[Computed]
    public function selectedBooking(): ?EventBooking
    {
        if (! $this->selectedBookingId) {
            return null;
        }

        return EventBooking::with(['items.eventService', 'eventSpace', 'guest'])->find($this->selectedBookingId);
    }

    public function select(int $bookingId): void
    {
        $this->selectedBookingId = $bookingId;
    }

    public function create(): void
    {
        $this->authorize('create', EventBooking::class);

        $this->reset(['eventSpaceId', 'title', 'attendeeCount']);
        $this->eventType = 'conference';
        $this->startAt = now()->addDay()->setTime(9, 0)->format('Y-m-d\TH:i');
        $this->endAt = now()->addDay()->setTime(13, 0)->format('Y-m-d\TH:i');
        $this->showForm = true;
    }

    public function save(CreateEventBookingAction $createBooking): void
    {
        $this->authorize('create', EventBooking::class);

        $this->validate([
            'eventSpaceId' => ['required', 'integer'],
            'title' => ['required', 'string', 'max:255'],
            'eventType' => ['required', 'string'],
            'startAt' => ['required', 'date'],
            'endAt' => ['required', 'date', 'after:startAt'],
            'attendeeCount' => ['nullable', 'integer', 'min:1'],
        ]);

        $branch = Branch::findOrFail($this->branchId);
        $space = EventSpace::findOrFail($this->eventSpaceId);

        $booking = $createBooking->handle(
            branch: $branch,
            eventSpace: $space,
            title: $this->title,
            eventType: $this->eventType,
            startAt: Carbon::parse($this->startAt),
            endAt: Carbon::parse($this->endAt),
            attendeeCount: $this->attendeeCount !== '' ? (int) $this->attendeeCount : null,
            createdBy: auth()->user(),
        );

        $this->showForm = false;
        $this->selectedBookingId = $booking->id;
        unset($this->bookings);
    }

    public function addItem(AddEventBookingItemAction $addItem): void
    {
        $this->authorize('update', $this->selectedBooking);

        $this->validate([
            'selectedServiceId' => ['required', 'integer'],
            'itemQuantity' => ['required', 'integer', 'min:1'],
        ]);

        $addItem->handle($this->selectedBooking, EventService::findOrFail($this->selectedServiceId), (int) $this->itemQuantity);

        $this->selectedServiceId = '';
        $this->itemQuantity = '1';
        unset($this->selectedBooking);
    }

    public function confirm(ConfirmEventBookingAction $confirmBooking): void
    {
        $this->authorize('update', $this->selectedBooking);

        $confirmBooking->handle($this->selectedBooking);
        unset($this->selectedBooking, $this->bookings);
    }

    public function cancel(CancelEventBookingAction $cancelBooking): void
    {
        $this->authorize('update', $this->selectedBooking);

        $cancelBooking->handle($this->selectedBooking);
        unset($this->selectedBooking, $this->bookings);
    }

    public function render(EventBillCalculator $billCalculator)
    {
        return view('livewire.events.event-booking-manager', [
            'bill' => $this->selectedBooking ? $billCalculator->calculate($this->selectedBooking) : null,
        ]);
    }
}
