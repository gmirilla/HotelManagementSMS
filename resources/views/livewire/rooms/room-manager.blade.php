<div>
    <div class="mb-6 flex flex-wrap items-center justify-between gap-3">
        <div>
            <h1 class="text-xl font-semibold text-slate-900">Rooms</h1>
            @if ($this->activeBranch)
                <p class="text-sm text-slate-500">{{ $this->activeBranch->name }}</p>
            @endif
        </div>

        <div class="flex items-center gap-3">
            @if ($this->accessibleBranches->count() > 1)
                <select wire:model.live="branchId" class="rounded-md border-slate-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                    @foreach ($this->accessibleBranches as $branch)
                        <option value="{{ $branch->id }}">{{ $branch->name }}</option>
                    @endforeach
                </select>
            @endif

            <select wire:model.live="statusFilter" class="rounded-md border-slate-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                <option value="">All statuses</option>
                @foreach ($statuses as $status)
                    <option value="{{ $status->value }}">{{ $status->label() }}</option>
                @endforeach
            </select>

            @can('create', [App\Models\Room::class, $branchId])
                <button wire:click="create" class="rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500">
                    New room
                </button>
            @endcan
        </div>
    </div>

    @if ($showForm)
        <div class="mb-6 rounded-lg border border-slate-200 bg-white p-6">
            <h2 class="mb-4 font-medium text-slate-800">{{ $editingId ? 'Edit room' : 'New room' }}</h2>

            <form wire:submit="save" class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <div>
                    <x-input-label for="roomNumber" value="Room number" />
                    <x-text-input id="roomNumber" type="text" wire:model="roomNumber" required />
                    <x-input-error :messages="$errors->get('roomNumber')" />
                </div>

                <div>
                    <x-input-label for="roomTypeId" value="Room type" />
                    <select id="roomTypeId" wire:model="roomTypeId" class="mt-1 block w-full rounded-md border-slate-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                        <option value="">Select a room type&hellip;</option>
                        @foreach ($this->roomTypes as $roomType)
                            <option value="{{ $roomType->id }}">{{ $roomType->name }}</option>
                        @endforeach
                    </select>
                    <x-input-error :messages="$errors->get('roomTypeId')" />
                </div>

                <div>
                    <x-input-label for="building" value="Building" />
                    <x-text-input id="building" type="text" wire:model="building" />
                </div>

                <div>
                    <x-input-label for="floor" value="Floor" />
                    <x-text-input id="floor" type="text" wire:model="floor" />
                </div>

                <div class="col-span-full flex gap-3">
                    <x-primary-button class="w-auto">Save</x-primary-button>
                    <button type="button" wire:click="$set('showForm', false)" class="text-sm text-slate-500 hover:text-slate-700">
                        Cancel
                    </button>
                </div>
            </form>
        </div>
    @endif

    <div class="grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-4 xl:grid-cols-6">
        @forelse ($this->rooms as $room)
            @php
                $statusStyles = [
                    'vacant_clean' => 'border-emerald-200 bg-emerald-50 text-emerald-800',
                    'vacant_dirty' => 'border-amber-200 bg-amber-50 text-amber-800',
                    'occupied' => 'border-indigo-200 bg-indigo-50 text-indigo-800',
                    'out_of_order' => 'border-red-200 bg-red-50 text-red-800',
                    'out_of_service' => 'border-slate-300 bg-slate-100 text-slate-700',
                ];
            @endphp
            <div wire:key="room-{{ $room->id }}" class="rounded-lg border p-4 {{ $statusStyles[$room->status->value] ?? 'border-slate-200 bg-white' }}">
                <p class="text-lg font-semibold">{{ $room->room_number }}</p>
                <p class="text-xs opacity-80">{{ $room->roomType->name }}</p>
                <p class="mt-2 text-xs font-medium">{{ $room->status->label() }}</p>

                <div class="mt-3 flex gap-2 text-xs">
                    @can('update', $room)
                        <button wire:click="edit({{ $room->id }})" class="font-medium underline">Edit</button>
                    @endcan
                    @can('delete', $room)
                        <button wire:click="delete({{ $room->id }})" wire:confirm="Delete this room?" class="font-medium underline">Delete</button>
                    @endcan
                </div>
            </div>
        @empty
            <p class="col-span-full text-sm text-slate-500">No rooms match the current filter.</p>
        @endforelse
    </div>
</div>
