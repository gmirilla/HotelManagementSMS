<div class="mx-auto max-w-3xl">
    <div class="mb-6 flex items-center justify-between">
        <div>
            <h1 class="text-xl font-semibold text-slate-900">Folio #{{ $folio->id }}</h1>
            <p class="text-sm text-slate-500">{{ $folio->guest->fullName() }} &middot; {{ $folio->branch->name }}</p>
        </div>
        <div class="text-right">
            <p class="text-xs uppercase text-slate-400">Balance</p>
            <p class="text-2xl font-semibold {{ $folio->balance_cents > 0 ? 'text-red-600' : 'text-emerald-600' }}">
                ${{ number_format($folio->balance_cents / 100, 2) }}
            </p>
        </div>
    </div>

    <div class="mb-6 grid grid-cols-1 gap-6 sm:grid-cols-2">
        <div class="rounded-xl border border-slate-200/70 bg-white shadow-sm shadow-slate-900/5 p-5">
            <div class="mb-3 flex items-center justify-between">
                <h2 class="font-medium text-slate-800">Charges</h2>
                @can('update', $folio)
                    <button wire:click="$toggle('showChargeForm')" class="text-sm font-medium text-brand-600 hover:text-brand-500">+ Add</button>
                @endcan
            </div>

            @if ($showChargeForm)
                <form wire:submit="addCharge" class="mb-4 space-y-2 rounded-md bg-slate-50 p-3">
                    <select wire:model="chargeType" class="block w-full rounded-md border-slate-300 text-sm shadow-sm">
                        @foreach ($chargeTypes as $type)
                            <option value="{{ $type->value }}">{{ ucfirst(str_replace('_', ' ', $type->value)) }}</option>
                        @endforeach
                    </select>
                    <x-text-input type="text" wire:model="chargeDescription" placeholder="Description" />
                    <x-input-error :messages="$errors->get('chargeDescription')" />
                    <x-text-input type="number" step="0.01" wire:model="chargeAmount" placeholder="Amount" />
                    <x-input-error :messages="$errors->get('chargeAmount')" />
                    <x-primary-button class="w-auto">Add charge</x-primary-button>
                </form>
            @endif

            <div class="divide-y divide-slate-100 text-sm">
                @forelse ($folio->charges as $charge)
                    <div class="flex justify-between py-2">
                        <span class="text-slate-600">{{ $charge->description }}</span>
                        <span class="font-medium text-slate-800">${{ number_format($charge->amount_cents / 100, 2) }}</span>
                    </div>
                @empty
                    <p class="py-2 text-slate-500">No charges yet.</p>
                @endforelse
            </div>
        </div>

        <div class="rounded-xl border border-slate-200/70 bg-white shadow-sm shadow-slate-900/5 p-5">
            <div class="mb-3 flex items-center justify-between">
                <h2 class="font-medium text-slate-800">Payments</h2>
                @can('update', $folio)
                    <button wire:click="$toggle('showPaymentForm')" class="text-sm font-medium text-brand-600 hover:text-brand-500">+ Add</button>
                @endcan
            </div>

            @if ($showPaymentForm)
                <form wire:submit="addPayment" class="mb-4 space-y-2 rounded-md bg-slate-50 p-3">
                    <select wire:model="paymentMethod" class="block w-full rounded-md border-slate-300 text-sm shadow-sm">
                        @foreach ($paymentMethods as $method)
                            <option value="{{ $method->value }}">{{ ucfirst(str_replace('_', ' ', $method->value)) }}</option>
                        @endforeach
                    </select>
                    <x-text-input type="number" step="0.01" wire:model="paymentAmount" placeholder="Amount" />
                    <x-input-error :messages="$errors->get('paymentAmount')" />
                    <x-primary-button class="w-auto">Record payment</x-primary-button>
                </form>
            @endif

            <div class="divide-y divide-slate-100 text-sm">
                @forelse ($folio->payments as $payment)
                    <div class="flex justify-between py-2">
                        <span class="text-slate-600">{{ ucfirst(str_replace('_', ' ', $payment->method->value)) }}</span>
                        <span class="font-medium text-slate-800">${{ number_format($payment->amount_cents / 100, 2) }}</span>
                    </div>
                @empty
                    <p class="py-2 text-slate-500">No payments yet.</p>
                @endforelse
            </div>
        </div>
    </div>
</div>
