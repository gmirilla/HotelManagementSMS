<div>
    <div class="mb-6 flex flex-wrap items-center justify-between gap-3">
        <div>
            <h1 class="text-xl font-semibold text-slate-900">Guest Feedback</h1>
            @if ($this->activeBranch)
                <p class="text-sm text-slate-500">{{ $this->activeBranch->name }}</p>
            @endif
        </div>

        <button wire:click="create" class="rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500">
            Log feedback
        </button>
    </div>

    @if ($showForm)
        <form wire:submit="save" class="mb-6 grid grid-cols-1 gap-4 rounded-lg border border-slate-200 bg-white p-6 sm:grid-cols-2">
            <div class="col-span-full">
                <x-input-label value="Guest (optional)" />
                <x-text-input type="text" wire:model.live.debounce.300ms="guestSearch" placeholder="Search guests…" />
                @if ($guestSearch !== '' && ! $guestId)
                    <div class="mt-1 max-h-40 overflow-y-auto rounded-md border border-slate-200 bg-white">
                        @forelse ($this->guestResults as $guest)
                            <button type="button" wire:click="$set('guestId', {{ $guest->id }})" class="block w-full px-3 py-2 text-left text-sm hover:bg-slate-50">
                                {{ $guest->fullName() }}
                            </button>
                        @empty
                            <p class="px-3 py-2 text-sm text-slate-500">No matching guests.</p>
                        @endforelse
                    </div>
                @endif
                @if ($guestId)
                    <p class="mt-1 text-sm text-emerald-600">Guest selected.</p>
                @endif
            </div>
            <div>
                <x-input-label value="Type" />
                <select wire:model="type" class="mt-1 block w-full rounded-md border-slate-300 text-sm shadow-sm">
                    @foreach ($types as $t)
                        <option value="{{ $t->value }}">{{ ucfirst($t->value) }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <x-input-label value="Subject" />
                <x-text-input type="text" wire:model="subject" />
                <x-input-error :messages="$errors->get('subject')" />
            </div>
            <div class="col-span-full">
                <x-input-label value="Description" />
                <textarea wire:model="description" rows="3" class="mt-1 block w-full rounded-md border-slate-300 text-sm shadow-sm"></textarea>
                <x-input-error :messages="$errors->get('description')" />
            </div>
            <div class="col-span-full flex gap-3">
                <x-primary-button class="w-auto">Save</x-primary-button>
                <button type="button" wire:click="$set('showForm', false)" class="text-sm text-slate-500 hover:text-slate-700">Cancel</button>
            </div>
        </form>
    @endif

    <div class="space-y-3">
        @forelse ($this->feedback as $item)
            <div wire:key="feedback-{{ $item->id }}" class="rounded-lg border border-slate-200 bg-white p-4">
                <div class="flex items-start justify-between">
                    <div>
                        <p class="font-medium text-slate-800">{{ $item->subject }}</p>
                        <p class="text-sm text-slate-500">
                            {{ ucfirst($item->type->value) }}
                            @if ($item->guest) — {{ $item->guest->fullName() }} @endif
                            · {{ $item->created_at->format('M j, Y') }}
                        </p>
                    </div>
                    <span @class([
                        'rounded-full px-2 py-0.5 text-xs font-medium',
                        'bg-amber-100 text-amber-700' => $item->status->value === 'open',
                        'bg-blue-100 text-blue-700' => $item->status->value === 'in_progress',
                        'bg-emerald-100 text-emerald-700' => $item->status->value === 'resolved',
                        'bg-slate-200 text-slate-600' => $item->status->value === 'closed',
                    ])>
                        {{ ucfirst(str_replace('_', ' ', $item->status->value)) }}
                    </span>
                </div>

                <p class="mt-2 text-sm text-slate-600">{{ $item->description }}</p>

                @if ($item->assignedTo)
                    <p class="mt-1 text-xs text-slate-500">Assigned to {{ $item->assignedTo->name }}</p>
                @endif

                @if ($item->resolution_notes)
                    <p class="mt-1 text-sm text-emerald-700"><span class="font-medium">Resolution:</span> {{ $item->resolution_notes }}</p>
                @endif

                @can('crm.manage')
                    @if (! in_array($item->status->value, ['resolved', 'closed']))
                        <div class="mt-3 flex gap-3 border-t border-slate-100 pt-3">
                            @unless ($item->assignedTo)
                                <button wire:click="assignToMe({{ $item->id }})" class="text-sm font-medium text-indigo-600 hover:text-indigo-500">Assign to me</button>
                            @endunless
                            <button wire:click="startResolve({{ $item->id }})" class="text-sm font-medium text-emerald-600 hover:text-emerald-500">Resolve</button>
                        </div>

                        @if ($resolvingId === $item->id)
                            <form wire:submit="resolve" class="mt-3 flex items-center gap-2">
                                <x-text-input type="text" wire:model="resolutionNotes" placeholder="Resolution notes" class="mt-0 flex-1" />
                                <x-primary-button class="w-auto">Confirm</x-primary-button>
                            </form>
                            <x-input-error :messages="$errors->get('resolutionNotes')" />
                        @endif
                    @endif
                @endcan
            </div>
        @empty
            <p class="text-sm text-slate-500">No feedback logged yet.</p>
        @endforelse
    </div>
</div>
