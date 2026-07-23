<div>
    <div class="mb-6 flex flex-wrap items-center justify-between gap-3">
        <div>
            <h1 class="text-xl font-semibold text-slate-900">Journal Entries</h1>
            @if ($this->activeBranch)
                <p class="text-sm text-slate-500">{{ $this->activeBranch->name }}</p>
            @endif
        </div>

        @can('create', App\Models\JournalEntry::class)
            <button wire:click="create" class="rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500">
                New journal entry
            </button>
        @endcan
    </div>

    @if ($showForm)
        <div class="mb-6 rounded-lg border border-slate-200 bg-white p-6">
            <form wire:submit="save" class="space-y-4">
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <div>
                        <x-input-label for="entryDate" value="Date" />
                        <x-text-input id="entryDate" type="date" wire:model="entryDate" />
                        <x-input-error :messages="$errors->get('entryDate')" />
                    </div>
                    <div>
                        <x-input-label for="memo" value="Memo" />
                        <x-text-input id="memo" type="text" wire:model="memo" />
                    </div>
                </div>

                <div class="space-y-2">
                    <x-input-label value="Lines" />
                    @foreach ($lines as $index => $line)
                        <div class="flex items-center gap-2">
                            <select wire:model="lines.{{ $index }}.account_id" class="flex-1 rounded-md border-slate-300 text-sm">
                                <option value="">Account&hellip;</option>
                                @foreach ($this->accounts as $account)
                                    <option value="{{ $account->id }}">{{ $account->code }} — {{ $account->name }}</option>
                                @endforeach
                            </select>
                            <select wire:model="lines.{{ $index }}.side" class="w-28 rounded-md border-slate-300 text-sm">
                                <option value="debit">Debit</option>
                                <option value="credit">Credit</option>
                            </select>
                            <input type="number" step="0.01" min="0" wire:model="lines.{{ $index }}.amount" placeholder="Amount"
                                class="w-32 rounded-md border-slate-300 text-sm">
                            <button type="button" wire:click="removeLine({{ $index }})" class="text-sm text-red-600">&times;</button>
                        </div>
                    @endforeach
                    <button type="button" wire:click="addLine" class="text-sm font-medium text-indigo-600 hover:text-indigo-500">+ Add line</button>
                    <x-input-error :messages="$errors->get('lines')" />
                </div>

                <div class="flex gap-3">
                    <x-primary-button class="w-auto">Post entry</x-primary-button>
                    <button type="button" wire:click="$set('showForm', false)" class="text-sm text-slate-500 hover:text-slate-700">Cancel</button>
                </div>
            </form>
        </div>
    @endif

    <div class="space-y-3">
        @forelse ($this->journalEntries as $entry)
            <div wire:key="je-{{ $entry->id }}" class="rounded-lg border border-slate-200 bg-white p-4">
                <div class="mb-2 flex items-center justify-between">
                    <p class="font-medium text-slate-800">{{ $entry->entry_date->format('M j, Y') }} &mdash; {{ $entry->memo }}</p>
                    <span class="text-xs text-slate-400">{{ $entry->createdBy?->name ?? 'System' }}</span>
                </div>
                <table class="w-full text-sm">
                    @foreach ($entry->lines as $line)
                        <tr>
                            <td class="py-0.5 text-slate-600">{{ $line->account->code }} — {{ $line->account->name }}</td>
                            <td class="py-0.5 text-right {{ $line->side->value === 'debit' ? 'text-slate-800' : 'text-slate-400' }}">
                                {{ $line->side->value === 'debit' ? number_format($line->amount_cents / 100, 2) : '' }}
                            </td>
                            <td class="py-0.5 text-right {{ $line->side->value === 'credit' ? 'text-slate-800' : 'text-slate-400' }}">
                                {{ $line->side->value === 'credit' ? number_format($line->amount_cents / 100, 2) : '' }}
                            </td>
                        </tr>
                    @endforeach
                </table>
            </div>
        @empty
            <p class="text-sm text-slate-500">No journal entries yet.</p>
        @endforelse
    </div>
</div>
