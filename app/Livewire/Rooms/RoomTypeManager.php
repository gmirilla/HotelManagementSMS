<?php

declare(strict_types=1);

namespace App\Livewire\Rooms;

use App\Livewire\Concerns\InteractsWithActiveBranch;
use App\Models\Amenity;
use App\Models\RoomType;
use Illuminate\Database\Eloquent\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('components.layouts.app')]
#[Title('Room Types')]
class RoomTypeManager extends Component
{
    use InteractsWithActiveBranch;

    public ?int $editingId = null;

    public bool $showForm = false;

    public string $name = '';

    public int $baseCapacityAdults = 2;

    public int $baseCapacityChildren = 0;

    public string $baseRate = '';

    public string $description = '';

    /** @var array<int, int> */
    public array $selectedAmenities = [];

    protected function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'baseCapacityAdults' => ['required', 'integer', 'min:1', 'max:20'],
            'baseCapacityChildren' => ['required', 'integer', 'min:0', 'max:20'],
            'baseRate' => ['required', 'numeric', 'min:0'],
            'description' => ['nullable', 'string', 'max:2000'],
            'selectedAmenities' => ['array'],
        ];
    }

    #[Computed]
    public function roomTypes(): Collection
    {
        return RoomType::query()
            ->where('branch_id', $this->branchId)
            ->with('amenities')
            ->withCount('rooms')
            ->orderBy('name')
            ->get();
    }

    #[Computed]
    public function amenities(): Collection
    {
        return Amenity::orderBy('name')->get();
    }

    public function create(): void
    {
        $this->authorize('create', RoomType::class);

        $this->resetForm();
        $this->showForm = true;
    }

    public function edit(int $roomTypeId): void
    {
        $roomType = RoomType::findOrFail($roomTypeId);
        $this->authorize('update', $roomType);

        $this->editingId = $roomType->id;
        $this->name = $roomType->name;
        $this->baseCapacityAdults = $roomType->base_capacity_adults;
        $this->baseCapacityChildren = $roomType->base_capacity_children;
        $this->baseRate = number_format($roomType->base_rate_cents / 100, 2, '.', '');
        $this->description = (string) $roomType->description;
        $this->selectedAmenities = $roomType->amenities->pluck('id')->all();
        $this->showForm = true;
    }

    public function save(): void
    {
        $this->validate();

        $roomType = $this->editingId ? RoomType::findOrFail($this->editingId) : new RoomType;
        $this->authorize($this->editingId ? 'update' : 'create', $this->editingId ? $roomType : RoomType::class);

        $roomType->fill([
            'branch_id' => $this->branchId,
            'name' => $this->name,
            'slug' => str($this->name)->slug(),
            'base_capacity_adults' => $this->baseCapacityAdults,
            'base_capacity_children' => $this->baseCapacityChildren,
            'base_rate_cents' => (int) round(((float) $this->baseRate) * 100),
            'description' => $this->description,
            'is_active' => true,
        ])->save();

        $roomType->amenities()->sync($this->selectedAmenities);

        unset($this->roomTypes);
        $this->resetForm();
        $this->showForm = false;
    }

    public function delete(int $roomTypeId): void
    {
        $roomType = RoomType::findOrFail($roomTypeId);
        $this->authorize('delete', $roomType);

        $roomType->delete();
        unset($this->roomTypes);
    }

    public function resetForm(): void
    {
        $this->reset(['editingId', 'name', 'baseCapacityAdults', 'baseCapacityChildren', 'baseRate', 'description', 'selectedAmenities']);
        $this->baseCapacityAdults = 2;
        $this->baseCapacityChildren = 0;
    }

    public function render()
    {
        return view('livewire.rooms.room-type-manager');
    }
}
