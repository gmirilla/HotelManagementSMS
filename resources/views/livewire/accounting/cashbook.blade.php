<div>
    <div class="mb-6 flex flex-wrap items-center justify-between gap-3">
        <div>
            <h1 class="text-xl font-semibold text-slate-900">Cashbook</h1>
            @if ($this->activeBranch)
                <p class="text-sm text-slate-500">{{ $this->activeBranch->name }}</p>
            @endif
        </div>

        <div class="flex items-center gap-3">
            <x-text-input type="date" wire:model.live="shiftDate" class="mt-0" />

            @can('accounting.manage')
                <button wire:click="create" class="rounded-md bg-brand-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-brand-500">
                    New entry
                </button>
            @endcan
        </div>
    </div>

    <div class="mb-6 rounded-xl border border-slate-200/70 bg-white shadow-sm shadow-slate-900/5 p-5">
        <p class="text-xs uppercase text-slate-400">Running balance for {{ \Illuminate\Support\Carbon::parse($shiftDate)->format('M j, Y') }}</p>
        <p class="text-2xl font-semibold {{ $this->runningBalanceCents >= 0 ? 'text-emerald-600' : 'text-red-600' }}">
            ${{ number_format($this->runningBalanceCents / 100, 2) }}
        </p>
    </div>

    @if ($showForm)
        <form wire:submit="save" class="mb-6 space-y-3 rounded-xl border border-slate-200/70 bg-white shadow-sm shadow-slate-900/5 p-6">
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                <div>
                    <x-input-label value="Type" />
                    <select wire:model="entryType" class="mt-1 block w-full rounded-md border-slate-300 text-sm shadow-sm">
                        <option value="cash_in">Cash in</option>
                        <option value="cash_out">Cash out</option>
                    </select>
                </div>
                <div>
                    <x-input-label value="Amount" />
                    <x-text-input type="number" step="0.01" min="0" wire:model="amount" />
                    <x-input-error :messages="$errors->get('amount')" />
                </div>
                <div>
                    <x-input-label value="Reason" />
                    <x-text-input type="text" wire:model="reason" />
                </div>
            </div>
            <div class="flex gap-3">
                <x-primary-button class="w-auto">Save</x-primary-button>
                <button type="button" wire:click="$set('showForm', false)" class="text-sm text-slate-500 hover:text-slate-700">Cancel</button>
            </div>
        </form>
    @endif

    <div class="overflow-hidden rounded-xl border border-slate-200/70 bg-white shadow-sm shadow-slate-900/5">
        <table class="min-w-full divide-y divide-slate-200 text-sm">
            <thead class="bg-slate-50 text-left text-xs font-medium uppercase tracking-wide text-slate-500">
                <tr>
                    <th class="px-4 py-3">Time</th>
                    <th class="px-4 py-3">Cashier</th>
                    <th class="px-4 py-3">Reason</th>
                    <th class="px-4 py-3 text-right">Amount</th>
                    <th class="px-4 py-3"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse ($this->entries as $entry)
                    <tr wire:key="cb-{{ $entry->id }}">
                        <td class="px-4 py-2 text-slate-500">{{ $entry->created_at->format('H:i') }}</td>
                        <td class="px-4 py-2 text-slate-700">{{ $entry->cashier->name }}</td>
                        <td class="px-4 py-2 text-slate-600">{{ $entry->reason }}</td>
                        <td class="px-4 py-2 text-right {{ $entry->entry_type->value === 'cash_in' ? 'text-emerald-600' : 'text-red-600' }}">
                            {{ $entry->entry_type->value === 'cash_in' ? '+' : '-' }}{{ number_format($entry->amount_cents / 100, 2) }}
                        </td>
                        <td class="px-4 py-2 text-right">
                            @can('accounting.manage')
                                <button wire:click="toggleReconciled({{ $entry->id }})" class="text-xs {{ $entry->reconciled ? 'text-emerald-600' : 'text-slate-400' }}">
                                    {{ $entry->reconciled ? 'Reconciled' : 'Mark reconciled' }}
                                </button>
                            @endcan
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="px-4 py-6 text-center text-slate-500">No entries for this date.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
