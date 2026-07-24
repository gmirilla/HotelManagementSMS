<div>
    <h1 class="mb-6 text-xl font-semibold text-slate-900">Loyalty Program</h1>

    <div class="mb-6 rounded-xl border border-slate-200/70 bg-white shadow-sm shadow-slate-900/5 p-4">
        <x-input-label value="Search guest" />
        <x-text-input type="text" wire:model.live.debounce.300ms="guestSearch" placeholder="Search by name…" />

        @if ($guestSearch !== '')
            <div class="mt-1 max-h-40 overflow-y-auto rounded-md border border-slate-200/70 bg-white shadow-sm shadow-slate-900/5">
                @forelse ($this->guestResults as $guest)
                    <button type="button" wire:click="selectGuest({{ $guest->id }})" class="block w-full px-3 py-2 text-left text-sm hover:bg-slate-50">
                        {{ $guest->fullName() }}
                    </button>
                @empty
                    <p class="px-3 py-2 text-sm text-slate-500">No matching guests.</p>
                @endforelse
            </div>
        @endif
    </div>

    @if ($this->selectedGuest)
        <div class="mb-6 rounded-xl border border-slate-200/70 bg-white shadow-sm shadow-slate-900/5 p-6">
            <div class="flex flex-wrap items-center justify-between gap-4">
                <div>
                    <p class="text-lg font-medium text-slate-800">{{ $this->selectedGuest->fullName() }}</p>
                    @if ($this->loyaltyAccount)
                        <p class="text-sm text-slate-500">Enrolled {{ $this->loyaltyAccount->enrolled_at->format('M j, Y') }}</p>
                    @else
                        <p class="text-sm text-slate-500">Not yet enrolled — earning points enrolls automatically.</p>
                    @endif
                </div>

                @if ($this->loyaltyAccount)
                    <div class="text-right">
                        <p class="text-2xl font-semibold text-brand-600">{{ number_format($pointsBalance) }} pts</p>
                        <span class="rounded-full bg-brand-100 px-2 py-0.5 text-xs font-medium text-brand-700">{{ ucfirst($tier->value) }}</span>
                    </div>
                @endif
            </div>

            @can('crm.manage')
                <div class="mt-4 flex gap-3 border-t border-slate-100 pt-4">
                    <button wire:click="startEarn" class="text-sm font-medium text-brand-600 hover:text-brand-500">Earn points</button>
                    @if ($this->loyaltyAccount)
                        <button wire:click="startRedeem" class="text-sm font-medium text-amber-600 hover:text-amber-500">Redeem points</button>
                    @endif
                </div>

                @if ($showEarnForm)
                    <form wire:submit="earn" class="mt-4 flex items-center gap-3">
                        <x-text-input type="number" min="1" wire:model="points" placeholder="Points" class="mt-0 w-32" />
                        <x-text-input type="text" wire:model="description" placeholder="Reason" class="mt-0 flex-1" />
                        <x-primary-button class="w-auto">Add</x-primary-button>
                    </form>
                    <x-input-error :messages="$errors->get('points')" />
                @endif

                @if ($showRedeemForm)
                    <form wire:submit="redeem" class="mt-4 flex items-center gap-3">
                        <x-text-input type="number" min="1" wire:model="points" placeholder="Points" class="mt-0 w-32" />
                        <x-text-input type="text" wire:model="description" placeholder="Reason" class="mt-0 flex-1" />
                        <x-primary-button class="w-auto">Redeem</x-primary-button>
                    </form>
                    <x-input-error :messages="$errors->get('points')" />
                @endif
            @endcan
        </div>

        @if ($this->loyaltyAccount)
            <div class="overflow-hidden rounded-xl border border-slate-200/70 bg-white shadow-sm shadow-slate-900/5">
                <table class="min-w-full divide-y divide-slate-200 text-sm">
                    <thead class="bg-slate-50 text-left text-xs font-medium uppercase tracking-wide text-slate-500">
                        <tr><th class="px-4 py-3">Date</th><th class="px-4 py-3">Type</th><th class="px-4 py-3">Points</th><th class="px-4 py-3">Description</th></tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse ($this->transactions as $transaction)
                            <tr wire:key="txn-{{ $transaction->id }}">
                                <td class="px-4 py-2 text-slate-600">{{ $transaction->transaction_date->format('M j, Y') }}</td>
                                <td class="px-4 py-2 text-slate-600">{{ ucfirst($transaction->transaction_type->value) }}</td>
                                <td class="px-4 py-2 {{ $transaction->points >= 0 ? 'text-emerald-600' : 'text-red-600' }}">
                                    {{ $transaction->points >= 0 ? '+' : '' }}{{ $transaction->points }}
                                </td>
                                <td class="px-4 py-2 text-slate-600">{{ $transaction->description }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="px-4 py-6 text-center text-slate-500">No transactions yet.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        @endif
    @endif
</div>
