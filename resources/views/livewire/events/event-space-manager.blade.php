<div>
    <div class="mb-6 flex flex-wrap items-center justify-between gap-3">
        <div>
            <h1 class="text-xl font-semibold text-slate-900">Event Spaces &amp; Services</h1>
            @if ($this->activeBranch)
                <p class="text-sm text-slate-500">{{ $this->activeBranch->name }}</p>
            @endif
        </div>

        @can('events.manage')
            @if ($tab === 'spaces')
                <button wire:click="createSpace" class="rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500">
                    New space
                </button>
            @else
                <button wire:click="createService" class="rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500">
                    New service
                </button>
            @endif
        @endcan
    </div>

    <div class="mb-6 flex gap-1 rounded-lg bg-slate-100 p-1 text-sm font-medium">
        <button wire:click="$set('tab', 'spaces')" @class(['flex-1 rounded-md py-2', 'bg-white shadow-sm text-slate-900' => $tab === 'spaces', 'text-slate-500' => $tab !== 'spaces'])>
            Event Spaces
        </button>
        <button wire:click="$set('tab', 'services')" @class(['flex-1 rounded-md py-2', 'bg-white shadow-sm text-slate-900' => $tab === 'services', 'text-slate-500' => $tab !== 'services'])>
            Catering &amp; Equipment
        </button>
    </div>

    @if ($showSpaceForm)
        <form wire:submit="saveSpace" class="mb-6 grid grid-cols-1 gap-4 rounded-lg border border-slate-200 bg-white p-6 sm:grid-cols-3">
            <div>
                <x-input-label value="Name" />
                <x-text-input type="text" wire:model="spaceName" />
                <x-input-error :messages="$errors->get('spaceName')" />
            </div>
            <div>
                <x-input-label value="Capacity" />
                <x-text-input type="number" min="1" wire:model="capacity" />
                <x-input-error :messages="$errors->get('capacity')" />
            </div>
            <div>
                <x-input-label value="Hourly rate" />
                <x-text-input type="number" step="0.01" min="0" wire:model="hourlyRate" />
                <x-input-error :messages="$errors->get('hourlyRate')" />
            </div>
            <div class="col-span-full flex gap-3">
                <x-primary-button class="w-auto">Save</x-primary-button>
                <button type="button" wire:click="$set('showSpaceForm', false)" class="text-sm text-slate-500 hover:text-slate-700">Cancel</button>
            </div>
        </form>
    @endif

    @if ($showServiceForm)
        <form wire:submit="saveService" class="mb-6 grid grid-cols-1 gap-4 rounded-lg border border-slate-200 bg-white p-6 sm:grid-cols-3">
            <div>
                <x-input-label value="Name" />
                <x-text-input type="text" wire:model="serviceName" />
                <x-input-error :messages="$errors->get('serviceName')" />
            </div>
            <div>
                <x-input-label value="Category" />
                <select wire:model="category" class="mt-1 block w-full rounded-md border-slate-300 text-sm shadow-sm">
                    @foreach ($categories as $c)
                        <option value="{{ $c->value }}">{{ ucfirst($c->value) }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <x-input-label value="Unit price" />
                <x-text-input type="number" step="0.01" min="0" wire:model="unitPrice" />
                <x-input-error :messages="$errors->get('unitPrice')" />
            </div>
            <div class="col-span-full flex gap-3">
                <x-primary-button class="w-auto">Save</x-primary-button>
                <button type="button" wire:click="$set('showServiceForm', false)" class="text-sm text-slate-500 hover:text-slate-700">Cancel</button>
            </div>
        </form>
    @endif

    @if ($tab === 'spaces')
        <div class="overflow-hidden rounded-lg border border-slate-200 bg-white">
            <table class="min-w-full divide-y divide-slate-200 text-sm">
                <thead class="bg-slate-50 text-left text-xs font-medium uppercase tracking-wide text-slate-500">
                    <tr><th class="px-4 py-3">Name</th><th class="px-4 py-3">Capacity</th><th class="px-4 py-3">Hourly rate</th><th class="px-4 py-3"></th></tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($this->spaces as $space)
                        <tr wire:key="space-{{ $space->id }}">
                            <td class="px-4 py-2 text-slate-800">{{ $space->name }}</td>
                            <td class="px-4 py-2 text-slate-600">{{ $space->capacity }}</td>
                            <td class="px-4 py-2 text-slate-600">${{ number_format($space->hourly_rate_cents / 100, 2) }}</td>
                            <td class="px-4 py-2 text-right">
                                @can('events.manage')
                                    <button wire:click="toggleSpaceActive({{ $space->id }})" class="text-xs {{ $space->is_active ? 'text-emerald-600' : 'text-slate-400' }}">
                                        {{ $space->is_active ? 'Active' : 'Inactive' }}
                                    </button>
                                @endcan
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="px-4 py-6 text-center text-slate-500">No event spaces yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    @endif

    @if ($tab === 'services')
        <div class="overflow-hidden rounded-lg border border-slate-200 bg-white">
            <table class="min-w-full divide-y divide-slate-200 text-sm">
                <thead class="bg-slate-50 text-left text-xs font-medium uppercase tracking-wide text-slate-500">
                    <tr><th class="px-4 py-3">Name</th><th class="px-4 py-3">Category</th><th class="px-4 py-3">Unit price</th></tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($this->services as $service)
                        <tr wire:key="service-{{ $service->id }}">
                            <td class="px-4 py-2 text-slate-800">{{ $service->name }}</td>
                            <td class="px-4 py-2 text-slate-600">{{ ucfirst($service->category->value) }}</td>
                            <td class="px-4 py-2 text-slate-600">${{ number_format($service->unit_price_cents / 100, 2) }} / {{ str_replace('_', ' ', $service->unit) }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="3" class="px-4 py-6 text-center text-slate-500">No services yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    @endif
</div>
