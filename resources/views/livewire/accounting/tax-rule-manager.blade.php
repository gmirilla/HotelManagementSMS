<div>
    <div class="mb-6 flex flex-wrap items-center justify-between gap-3">
        <div>
            <h1 class="text-xl font-semibold text-slate-900">Tax Rules</h1>
            @if ($this->activeBranch)
                <p class="text-sm text-slate-500">{{ $this->activeBranch->name }}</p>
            @endif
        </div>

        @can('accounting.manage')
            <button wire:click="create" class="rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500">
                New tax rule
            </button>
        @endcan
    </div>

    @if ($showForm)
        <form wire:submit="save" class="mb-6 grid grid-cols-1 gap-4 rounded-lg border border-slate-200 bg-white p-6 sm:grid-cols-3">
            <div>
                <x-input-label value="Name" />
                <x-text-input type="text" wire:model="name" />
                <x-input-error :messages="$errors->get('name')" />
            </div>
            <div>
                <x-input-label value="Rate (%)" />
                <x-text-input type="number" step="0.01" min="0" max="100" wire:model="ratePercent" />
                <x-input-error :messages="$errors->get('ratePercent')" />
            </div>
            <div>
                <x-input-label value="Applies to" />
                <select wire:model="appliesTo" class="mt-1 block w-full rounded-md border-slate-300 text-sm shadow-sm">
                    @foreach ($applyOptions as $option)
                        <option value="{{ $option->value }}">{{ ucfirst($option->value) }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-span-full flex gap-3">
                <x-primary-button class="w-auto">Save</x-primary-button>
                <button type="button" wire:click="$set('showForm', false)" class="text-sm text-slate-500 hover:text-slate-700">Cancel</button>
            </div>
        </form>
    @endif

    <div class="overflow-hidden rounded-lg border border-slate-200 bg-white">
        <table class="min-w-full divide-y divide-slate-200 text-sm">
            <thead class="bg-slate-50 text-left text-xs font-medium uppercase tracking-wide text-slate-500">
                <tr><th class="px-4 py-3">Name</th><th class="px-4 py-3">Rate</th><th class="px-4 py-3">Applies to</th><th class="px-4 py-3"></th></tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse ($this->taxRules as $rule)
                    <tr wire:key="tax-{{ $rule->id }}">
                        <td class="px-4 py-2 text-slate-800">{{ $rule->name }}</td>
                        <td class="px-4 py-2 text-slate-600">{{ $rule->rate_percent }}%</td>
                        <td class="px-4 py-2 text-slate-600">{{ ucfirst($rule->applies_to->value) }}</td>
                        <td class="px-4 py-2 text-right">
                            @can('accounting.manage')
                                <button wire:click="toggleActive({{ $rule->id }})" class="text-xs {{ $rule->is_active ? 'text-emerald-600' : 'text-slate-400' }}">
                                    {{ $rule->is_active ? 'Active' : 'Inactive' }}
                                </button>
                            @endcan
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="4" class="px-4 py-6 text-center text-slate-500">No tax rules yet.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
