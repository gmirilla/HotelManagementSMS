<?php

declare(strict_types=1);

namespace App\Livewire\Events;

use App\Domain\Event\Enums\EventServiceCategory;
use App\Livewire\Concerns\InteractsWithActiveBranch;
use App\Models\EventService;
use App\Models\EventSpace;
use Illuminate\Database\Eloquent\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('components.layouts.app')]
#[Title('Event Spaces & Services')]
class EventSpaceManager extends Component
{
    use InteractsWithActiveBranch;

    public string $tab = 'spaces';

    public bool $showSpaceForm = false;

    public string $spaceName = '';

    public string $capacity = '';

    public string $hourlyRate = '';

    public bool $showServiceForm = false;

    public string $serviceName = '';

    public string $category = 'catering';

    public string $unitPrice = '';

    public string $unit = 'flat';

    #[Computed]
    public function spaces(): Collection
    {
        return EventSpace::where('branch_id', $this->branchId)->orderBy('name')->get();
    }

    #[Computed]
    public function services(): Collection
    {
        return EventService::where('branch_id', $this->branchId)->orderBy('name')->get();
    }

    public function createSpace(): void
    {
        abort_unless(auth()->user()->hasPermissionTo('events.manage'), 403);

        $this->reset(['spaceName', 'capacity', 'hourlyRate']);
        $this->showSpaceForm = true;
    }

    public function saveSpace(): void
    {
        abort_unless(auth()->user()->hasPermissionTo('events.manage'), 403);

        $this->validate([
            'spaceName' => ['required', 'string', 'max:255'],
            'capacity' => ['required', 'integer', 'min:1'],
            'hourlyRate' => ['required', 'numeric', 'min:0'],
        ]);

        EventSpace::create([
            'branch_id' => $this->branchId,
            'name' => $this->spaceName,
            'capacity' => $this->capacity,
            'hourly_rate_cents' => (int) round(((float) $this->hourlyRate) * 100),
        ]);

        $this->showSpaceForm = false;
        unset($this->spaces);
    }

    public function toggleSpaceActive(int $spaceId): void
    {
        abort_unless(auth()->user()->hasPermissionTo('events.manage'), 403);

        $space = EventSpace::findOrFail($spaceId);
        $space->update(['is_active' => ! $space->is_active]);
        unset($this->spaces);
    }

    public function createService(): void
    {
        abort_unless(auth()->user()->hasPermissionTo('events.manage'), 403);

        $this->reset(['serviceName', 'unitPrice']);
        $this->category = 'catering';
        $this->unit = 'flat';
        $this->showServiceForm = true;
    }

    public function saveService(): void
    {
        abort_unless(auth()->user()->hasPermissionTo('events.manage'), 403);

        $this->validate([
            'serviceName' => ['required', 'string', 'max:255'],
            'category' => ['required', 'string'],
            'unitPrice' => ['required', 'numeric', 'min:0'],
        ]);

        EventService::create([
            'branch_id' => $this->branchId,
            'name' => $this->serviceName,
            'category' => $this->category,
            'unit_price_cents' => (int) round(((float) $this->unitPrice) * 100),
            'unit' => $this->unit,
        ]);

        $this->showServiceForm = false;
        unset($this->services);
    }

    public function render()
    {
        return view('livewire.events.event-space-manager', ['categories' => EventServiceCategory::cases()]);
    }
}
