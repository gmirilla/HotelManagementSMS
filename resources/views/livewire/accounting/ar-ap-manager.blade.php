<div>
    <div class="mb-6 flex flex-wrap items-center justify-between gap-3">
        <div>
            <h1 class="text-xl font-semibold text-slate-900">Receivables &amp; Payables</h1>
            @if ($this->activeBranch)
                <p class="text-sm text-slate-500">{{ $this->activeBranch->name }}</p>
            @endif
        </div>
    </div>

    <div class="mb-6 flex flex-wrap items-center justify-between gap-3">
        <div class="flex gap-1 rounded-lg bg-slate-100 p-1 text-sm font-medium">
            <button wire:click="$set('tab', 'receivables')" @class(['flex-1 rounded-md py-2 px-3', 'bg-white shadow-sm text-slate-900' => $tab === 'receivables', 'text-slate-500' => $tab !== 'receivables'])>
                Receivables ({{ $this->receivables->count() }})
            </button>
            <button wire:click="$set('tab', 'payables')" @class(['flex-1 rounded-md py-2 px-3', 'bg-white shadow-sm text-slate-900' => $tab === 'payables', 'text-slate-500' => $tab !== 'payables'])>
                Payables ({{ $this->payables->count() }})
            </button>
        </div>

        <button wire:click="exportCsv" class="rounded-md border border-slate-300 bg-white px-3 py-1.5 text-sm font-medium text-slate-700 shadow-sm hover:bg-slate-50">
            Export CSV
        </button>
    </div>

    @if ($tab === 'receivables')
        <div class="space-y-3">
            @forelse ($this->receivables as $ar)
                <div wire:key="ar-{{ $ar->id }}" class="rounded-xl border border-slate-200/70 bg-white shadow-sm shadow-slate-900/5 p-4">
                    <div class="flex items-start justify-between">
                        <div>
                            <p class="font-medium text-slate-800">{{ $ar->corporateAccount->company_name }}</p>
                            <p class="text-sm text-slate-500">
                                Due {{ $ar->due_date->format('M j, Y') }}
                                @if ($ar->isOverdue())
                                    <span class="ml-1 font-medium text-red-600">Overdue</span>
                                @endif
                            </p>
                        </div>
                        <div class="text-right">
                            <p class="font-semibold text-slate-800">₦{{ number_format($ar->outstandingCents() / 100, 2) }}</p>
                            <span class="rounded-full bg-slate-100 px-2 py-0.5 text-xs text-slate-600">{{ ucfirst(str_replace('_', ' ', $ar->status->value)) }}</span>
                        </div>
                    </div>

                    @can('accounting.manage')
                        @if ($ar->outstandingCents() > 0)
                            @if ($payingArId === $ar->id)
                                <form wire:submit="recordArPayment" class="mt-3 flex items-center gap-2 border-t border-slate-100 pt-3">
                                    <x-text-input type="number" step="0.01" wire:model="paymentAmount" placeholder="Amount" class="mt-0 w-32" />
                                    <x-primary-button class="w-auto">Record payment</x-primary-button>
                                </form>
                                <x-input-error :messages="$errors->get('paymentAmount')" />
                            @else
                                <button wire:click="startArPayment({{ $ar->id }})" class="mt-3 text-sm font-medium text-brand-600 hover:text-brand-500">
                                    Record payment
                                </button>
                            @endif
                        @endif
                    @endcan
                </div>
            @empty
                <p class="text-sm text-slate-500">No receivables.</p>
            @endforelse
        </div>
    @endif

    @if ($tab === 'payables')
        <div class="space-y-3">
            @forelse ($this->payables as $ap)
                <div wire:key="ap-{{ $ap->id }}" class="rounded-xl border border-slate-200/70 bg-white shadow-sm shadow-slate-900/5 p-4">
                    <div class="flex items-start justify-between">
                        <div>
                            <p class="font-medium text-slate-800">{{ $ap->supplier->name }}</p>
                            <p class="text-sm text-slate-500">
                                Due {{ $ap->due_date->format('M j, Y') }}
                                @if ($ap->isOverdue())
                                    <span class="ml-1 font-medium text-red-600">Overdue</span>
                                @endif
                            </p>
                        </div>
                        <div class="text-right">
                            <p class="font-semibold text-slate-800">₦{{ number_format($ap->outstandingCents() / 100, 2) }}</p>
                            <span class="rounded-full bg-slate-100 px-2 py-0.5 text-xs text-slate-600">{{ ucfirst(str_replace('_', ' ', $ap->status->value)) }}</span>
                        </div>
                    </div>

                    @can('accounting.manage')
                        @if ($ap->outstandingCents() > 0)
                            @if ($payingApId === $ap->id)
                                <form wire:submit="recordApPayment" class="mt-3 flex items-center gap-2 border-t border-slate-100 pt-3">
                                    <x-text-input type="number" step="0.01" wire:model="paymentAmount" placeholder="Amount" class="mt-0 w-32" />
                                    <x-primary-button class="w-auto">Record payment</x-primary-button>
                                </form>
                                <x-input-error :messages="$errors->get('paymentAmount')" />
                            @else
                                <button wire:click="startApPayment({{ $ap->id }})" class="mt-3 text-sm font-medium text-brand-600 hover:text-brand-500">
                                    Record payment
                                </button>
                            @endif
                        @endif
                    @endcan
                </div>
            @empty
                <p class="text-sm text-slate-500">No payables.</p>
            @endforelse
        </div>
    @endif
</div>
