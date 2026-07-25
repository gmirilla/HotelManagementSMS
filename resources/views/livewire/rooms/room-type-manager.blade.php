<div>
    <div class="mb-6 flex items-center justify-between">
        <div>
            <h1 class="text-xl font-semibold text-slate-900">Room Types</h1>
            @if ($this->activeBranch)
                <p class="text-sm text-slate-500">{{ $this->activeBranch->name }}</p>
            @endif
        </div>

        <div class="flex items-center gap-3">
            @if ($this->accessibleBranches->count() > 1)
                <select wire:model.live="branchId" class="rounded-md border-slate-300 text-sm shadow-sm focus:border-brand-500 focus:ring-brand-500">
                    @foreach ($this->accessibleBranches as $branch)
                        <option value="{{ $branch->id }}">{{ $branch->name }}</option>
                    @endforeach
                </select>
            @endif

            @can('create', App\Models\RoomType::class)
                <button wire:click="create" class="rounded-md bg-brand-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-brand-500">
                    New room type
                </button>
            @endcan
        </div>
    </div>

    @if ($showForm)
        <div class="mb-6 rounded-xl border border-slate-200/70 bg-white shadow-sm shadow-slate-900/5 p-6">
            <h2 class="mb-4 font-medium text-slate-800">{{ $editingId ? 'Edit room type' : 'New room type' }}</h2>

            <form wire:submit="save" class="space-y-4">
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <div>
                        <x-input-label for="name" value="Name" />
                        <x-text-input id="name" type="text" wire:model="name" required />
                        <x-input-error :messages="$errors->get('name')" />
                    </div>

                    <div>
                        <x-input-label for="baseRate" value="Base rate (per night)" />
                        <x-text-input id="baseRate" type="number" step="0.01" min="0" wire:model="baseRate" required />
                        <x-input-error :messages="$errors->get('baseRate')" />
                    </div>

                    <div>
                        <x-input-label for="baseCapacityAdults" value="Max adults" />
                        <x-text-input id="baseCapacityAdults" type="number" min="1" wire:model="baseCapacityAdults" required />
                    </div>

                    <div>
                        <x-input-label for="baseCapacityChildren" value="Max children" />
                        <x-text-input id="baseCapacityChildren" type="number" min="0" wire:model="baseCapacityChildren" required />
                    </div>
                </div>

                <div>
                    <x-input-label for="description" value="Description" />
                    <textarea id="description" wire:model="description" rows="3"
                        class="mt-1 block w-full rounded-md border-slate-300 shadow-sm focus:border-brand-500 focus:ring-brand-500 sm:text-sm"></textarea>
                </div>

                <div>
                    <x-input-label value="Amenities" />
                    <div class="mt-2 grid grid-cols-2 gap-2 sm:grid-cols-3">
                        @foreach ($this->amenities as $amenity)
                            <label class="flex items-center gap-2 text-sm text-slate-700">
                                <input type="checkbox" value="{{ $amenity->id }}" wire:model="selectedAmenities"
                                    class="rounded border-slate-300 text-brand-600 focus:ring-brand-500">
                                {{ $amenity->name }}
                            </label>
                        @endforeach
                    </div>
                </div>

                <div class="flex gap-3">
                    <x-primary-button class="w-auto">Save</x-primary-button>
                    <button type="button" wire:click="$set('showForm', false)" class="text-sm text-slate-500 hover:text-slate-700">
                        Cancel
                    </button>
                </div>
            </form>
        </div>
    @endif

    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
        @forelse ($this->roomTypes as $roomType)
            <div class="rounded-xl border border-slate-200/70 bg-white shadow-sm shadow-slate-900/5 p-5" wire:key="room-type-{{ $roomType->id }}">
                <div class="flex items-start justify-between">
                    <div>
                        <h3 class="font-medium text-slate-800">{{ $roomType->name }}</h3>
                        <p class="text-sm text-slate-500">{{ $roomType->rooms_count }} room(s)</p>
                    </div>
                    <span class="text-sm font-semibold text-slate-800">₦{{ number_format($roomType->base_rate_cents / 100, 2) }}/night</span>
                </div>

                <p class="mt-2 text-sm text-slate-600">{{ $roomType->description }}</p>

                <div class="mt-3 flex flex-wrap gap-1">
                    @foreach ($roomType->amenities as $amenity)
                        <span class="rounded-full bg-slate-100 px-2 py-0.5 text-xs text-slate-600">{{ $amenity->name }}</span>
                    @endforeach
                </div>

                <div class="mt-4 flex gap-3 text-sm">
                    @can('update', $roomType)
                        <button wire:click="edit({{ $roomType->id }})" class="font-medium text-brand-600 hover:text-brand-500">Edit</button>
                    @endcan
                    @can('delete', $roomType)
                        <button wire:click="delete({{ $roomType->id }})" wire:confirm="Delete this room type?" class="font-medium text-red-600 hover:text-red-500">
                            Delete
                        </button>
                    @endcan
                </div>
            </div>
        @empty
            <p class="col-span-full text-sm text-slate-500">No room types yet for this branch.</p>
        @endforelse
    </div>
</div>
