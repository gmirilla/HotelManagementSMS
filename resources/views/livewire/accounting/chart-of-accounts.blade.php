<div>
    <div class="mb-6 flex flex-wrap items-center justify-between gap-3">
        <div>
            <h1 class="text-xl font-semibold text-slate-900">Chart of Accounts</h1>
            @if ($this->activeBranch)
                <p class="text-sm text-slate-500">{{ $this->activeBranch->name }}</p>
            @endif
        </div>

        <div class="flex items-center gap-3">
            @if ($this->accessibleBranches->count() > 1)
                <select wire:model.live="branchId" class="rounded-md border-slate-300 text-sm shadow-sm">
                    @foreach ($this->accessibleBranches as $branch)
                        <option value="{{ $branch->id }}">{{ $branch->name }}</option>
                    @endforeach
                </select>
            @endif

            @can('create', App\Models\Account::class)
                <button wire:click="create" class="rounded-md bg-brand-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-brand-500">
                    New account
                </button>
            @endcan
        </div>
    </div>

    @if ($showForm)
        <div class="mb-6 rounded-xl border border-slate-200/70 bg-white shadow-sm shadow-slate-900/5 p-6">
            <form wire:submit="save" class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <div>
                    <x-input-label for="code" value="Code" />
                    <x-text-input id="code" type="text" wire:model="code" required />
                    <x-input-error :messages="$errors->get('code')" />
                </div>
                <div>
                    <x-input-label for="name" value="Name" />
                    <x-text-input id="name" type="text" wire:model="name" required />
                    <x-input-error :messages="$errors->get('name')" />
                </div>
                <div>
                    <x-input-label for="accountType" value="Type" />
                    <select id="accountType" wire:model="accountType" class="mt-1 block w-full rounded-md border-slate-300 text-sm shadow-sm">
                        @foreach ($accountTypes as $type)
                            <option value="{{ $type->value }}">{{ ucfirst($type->value) }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <x-input-label for="parentAccountId" value="Parent account (optional)" />
                    <select id="parentAccountId" wire:model="parentAccountId" class="mt-1 block w-full rounded-md border-slate-300 text-sm shadow-sm">
                        <option value="">None</option>
                        @foreach ($this->accounts as $account)
                            <option value="{{ $account->id }}">{{ $account->code }} — {{ $account->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-span-full flex gap-3">
                    <x-primary-button class="w-auto">Save</x-primary-button>
                    <button type="button" wire:click="$set('showForm', false)" class="text-sm text-slate-500 hover:text-slate-700">Cancel</button>
                </div>
            </form>
        </div>
    @endif

    <div class="overflow-hidden rounded-xl border border-slate-200/70 bg-white shadow-sm shadow-slate-900/5">
        <table class="min-w-full divide-y divide-slate-200 text-sm">
            <thead class="bg-slate-50 text-left text-xs font-medium uppercase tracking-wide text-slate-500">
                <tr>
                    <th class="px-4 py-3">Code</th>
                    <th class="px-4 py-3">Name</th>
                    <th class="px-4 py-3">Type</th>
                    <th class="px-4 py-3">Parent</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse ($this->accounts as $account)
                    <tr wire:key="account-{{ $account->id }}">
                        <td class="px-4 py-3 font-mono text-slate-600">{{ $account->code }}</td>
                        <td class="px-4 py-3 text-slate-800">{{ $account->name }}</td>
                        <td class="px-4 py-3">
                            <span class="rounded-full bg-slate-100 px-2 py-0.5 text-xs text-slate-600">{{ ucfirst($account->account_type->value) }}</span>
                        </td>
                        <td class="px-4 py-3 text-slate-500">{{ $account->parent?->name }}</td>
                    </tr>
                @empty
                    <tr><td colspan="4" class="px-4 py-6 text-center text-slate-500">No accounts yet.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
