<?php

declare(strict_types=1);

namespace App\Livewire\Rooms;

use App\Domain\Room\Enums\HousekeepingStatus;
use App\Domain\Room\Enums\RoomStatus;
use App\Livewire\Concerns\InteractsWithActiveBranch;
use App\Models\Room;
use App\Models\RoomType;
use Illuminate\Database\Eloquent\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('components.layouts.app')]
#[Title('Rooms')]
class RoomManager extends Component
{
    use InteractsWithActiveBranch;

    public ?int $editingId = null;

    public bool $showForm = false;

    public string $roomNumber = '';

    public ?int $roomTypeId = null;

    public string $building = '';

    public string $floor = '';

    public string $statusFilter = '';

    protected function rules(): array
    {
        return [
            'roomNumber' => ['required', 'string', 'max:50'],
            'roomTypeId' => ['required', 'integer', 'exists:room_types,id'],
            'building' => ['nullable', 'string', 'max:100'],
            'floor' => ['nullable', 'string', 'max:20'],
        ];
    }

    #[Computed]
    public function rooms(): Collection
    {
        return Room::query()
            ->where('branch_id', $this->branchId)
            ->with('roomType')
            ->when($this->statusFilter, fn ($query) => $query->where('status', $this->statusFilter))
            ->orderBy('floor')
            ->orderBy('room_number')
            ->get();
    }

    #[Computed]
    public function roomTypes(): Collection
    {
        return RoomType::where('branch_id', $this->branchId)->orderBy('name')->get();
    }

    public function create(): void
    {
        $this->authorize('create', [Room::class, $this->branchId]);

        $this->resetForm();
        $this->showForm = true;
    }

    public function edit(int $roomId): void
    {
        $room = Room::findOrFail($roomId);
        $this->authorize('update', $room);

        $this->editingId = $room->id;
        $this->roomNumber = $room->room_number;
        $this->roomTypeId = $room->room_type_id;
        $this->building = (string) $room->building;
        $this->floor = (string) $room->floor;
        $this->showForm = true;
    }

    public function save(): void
    {
        $this->validate();

        $room = $this->editingId ? Room::findOrFail($this->editingId) : new Room;
        $this->authorize($this->editingId ? 'update' : 'create', $this->editingId ? $room : [Room::class, $this->branchId]);

        $isNew = ! $this->editingId;

        $room->fill([
            'branch_id' => $this->branchId,
            'room_type_id' => $this->roomTypeId,
            'room_number' => $this->roomNumber,
            'building' => $this->building,
            'floor' => $this->floor,
        ]);

        if ($isNew) {
            $room->status = RoomStatus::VacantClean;
            $room->housekeeping_status = HousekeepingStatus::Clean;
            $room->is_active = true;
        }

        $room->save();

        unset($this->rooms);
        $this->resetForm();
        $this->showForm = false;
    }

    public function delete(int $roomId): void
    {
        $room = Room::findOrFail($roomId);
        $this->authorize('delete', $room);

        $room->delete();
        unset($this->rooms);
    }

    public function resetForm(): void
    {
        $this->reset(['editingId', 'roomNumber', 'roomTypeId', 'building', 'floor']);
    }

    public function render()
    {
        return view('livewire.rooms.room-manager', ['statuses' => RoomStatus::cases()]);
    }
}
