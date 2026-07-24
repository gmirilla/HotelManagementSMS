<div>
    <div class="mb-6 flex flex-wrap items-center justify-between gap-3">
        <div>
            <h1 class="text-xl font-semibold text-slate-900">Maintenance</h1>
            @if ($this->activeBranch)
                <p class="text-sm text-slate-500">{{ $this->activeBranch->name }}</p>
            @endif
        </div>

        <div class="flex items-center gap-3">
            <select wire:model.live="statusFilter" class="rounded-md border-slate-300 text-sm shadow-sm">
                <option value="">All statuses</option>
                @foreach ($statuses as $status)
                    <option value="{{ $status->value }}">{{ ucfirst($status->value) }}</option>
                @endforeach
            </select>

            @can('create', App\Models\MaintenanceWorkOrder::class)
                <button wire:click="$toggle('showForm')" class="rounded-md bg-brand-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-brand-500">
                    New work order
                </button>
            @endcan
        </div>
    </div>

    @if ($showForm)
        <div class="mb-6 rounded-xl border border-slate-200/70 bg-white shadow-sm shadow-slate-900/5 p-6">
            <form wire:submit="create" class="space-y-4">
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <div>
                        <x-input-label for="roomId" value="Room (optional)" />
                        <select id="roomId" wire:model="roomId" class="mt-1 block w-full rounded-md border-slate-300 text-sm shadow-sm">
                            <option value="">Not room-specific</option>
                            @foreach ($this->rooms as $room)
                                <option value="{{ $room->id }}">{{ $room->room_number }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <x-input-label for="priority" value="Priority" />
                        <select id="priority" wire:model="priority" class="mt-1 block w-full rounded-md border-slate-300 text-sm shadow-sm">
                            @foreach ($priorities as $p)
                                <option value="{{ $p->value }}">{{ ucfirst($p->value) }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div>
                    <x-input-label for="description" value="Description" />
                    <textarea id="description" wire:model="description" rows="3"
                        class="mt-1 block w-full rounded-md border-slate-300 shadow-sm sm:text-sm"></textarea>
                    <x-input-error :messages="$errors->get('description')" />
                </div>

                @if ($roomId)
                    <label class="flex items-center gap-2 text-sm text-slate-700">
                        <input type="checkbox" wire:model="takeRoomOutOfOrder" class="rounded border-slate-300 text-brand-600">
                        Take this room out of order until resolved
                    </label>
                @endif

                <x-primary-button class="w-auto">Create work order</x-primary-button>
            </form>
        </div>
    @endif

    <div class="space-y-3">
        @forelse ($this->workOrders as $workOrder)
            <div wire:key="wo-{{ $workOrder->id }}" class="rounded-xl border border-slate-200/70 bg-white shadow-sm shadow-slate-900/5 p-4">
                <div class="flex items-start justify-between">
                    <div>
                        <p class="font-medium text-slate-800">
                            {{ $workOrder->room ? 'Room '.$workOrder->room->room_number : 'General' }}
                            <span @class([
                                'ml-2 rounded-full px-2 py-0.5 text-xs font-medium',
                                'bg-red-100 text-red-700' => $workOrder->priority->value === 'urgent',
                                'bg-amber-100 text-amber-700' => $workOrder->priority->value === 'high',
                                'bg-slate-100 text-slate-600' => in_array($workOrder->priority->value, ['medium', 'low']),
                            ])>{{ ucfirst($workOrder->priority->value) }}</span>
                        </p>
                        <p class="mt-1 text-sm text-slate-600">{{ $workOrder->description }}</p>
                        <p class="mt-1 text-xs text-slate-400">Reported by {{ $workOrder->reportedBy->name }}</p>
                    </div>
                    <span class="rounded-full bg-slate-100 px-2 py-0.5 text-xs text-slate-600">{{ ucfirst($workOrder->status->value) }}</span>
                </div>

                @can('update', $workOrder)
                    @if ($workOrder->status->value === 'open' || $workOrder->status->value === 'in_progress')
                        @if ($completingId === $workOrder->id)
                            <div class="mt-3 flex items-end gap-2 border-t border-slate-100 pt-3">
                                <div>
                                    <x-input-label value="Parts cost" />
                                    <x-text-input type="number" step="0.01" wire:model="partsCost" class="w-28" />
                                </div>
                                <div>
                                    <x-input-label value="Labor cost" />
                                    <x-text-input type="number" step="0.01" wire:model="laborCost" class="w-28" />
                                </div>
                                <button wire:click="complete" class="rounded-md bg-brand-600 px-3 py-1.5 text-sm font-medium text-white hover:bg-brand-500">
                                    Mark complete
                                </button>
                            </div>
                        @else
                            <button wire:click="startCompleting({{ $workOrder->id }})" class="mt-3 text-sm font-medium text-brand-600 hover:text-brand-500">
                                Mark complete
                            </button>
                        @endif
                    @endif
                @endcan

                @can('verify', $workOrder)
                    @if ($workOrder->status->value === 'completed')
                        <button wire:click="verify({{ $workOrder->id }})" class="mt-3 text-sm font-medium text-emerald-600 hover:text-emerald-500">
                            Verify &amp; close
                        </button>
                    @endif
                @endcan
            </div>
        @empty
            <p class="text-sm text-slate-500">No work orders match the current filter.</p>
        @endforelse
    </div>
</div>
