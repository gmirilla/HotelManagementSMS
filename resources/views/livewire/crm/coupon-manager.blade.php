<div>
    <div class="mb-6 flex flex-wrap items-center justify-between gap-3">
        <div>
            <h1 class="text-xl font-semibold text-slate-900">Coupons &amp; Promotions</h1>
            @if ($this->activeBranch)
                <p class="text-sm text-slate-500">{{ $this->activeBranch->name }}</p>
            @endif
        </div>

        @can('crm.manage')
            <button wire:click="create" class="rounded-md bg-brand-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-brand-500">
                New coupon
            </button>
        @endcan
    </div>

    @if ($showForm)
        <form wire:submit="save" class="mb-6 grid grid-cols-1 gap-4 rounded-xl border border-slate-200/70 bg-white shadow-sm shadow-slate-900/5 p-6 sm:grid-cols-3">
            <div>
                <x-input-label value="Code" />
                <x-text-input type="text" wire:model="code" />
                <x-input-error :messages="$errors->get('code')" />
            </div>
            <div>
                <x-input-label value="Name" />
                <x-text-input type="text" wire:model="name" />
                <x-input-error :messages="$errors->get('name')" />
            </div>
            <div>
                <x-input-label value="Scope" />
                <select wire:model="scope" class="mt-1 block w-full rounded-md border-slate-300 text-sm shadow-sm">
                    @foreach ($scopes as $s)
                        <option value="{{ $s->value }}">{{ ucfirst($s->value) }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <x-input-label value="Discount type" />
                <select wire:model="discountType" class="mt-1 block w-full rounded-md border-slate-300 text-sm shadow-sm">
                    @foreach ($discountTypes as $t)
                        <option value="{{ $t->value }}">{{ ucfirst($t->value) }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <x-input-label value="Discount value (% or cents)" />
                <x-text-input type="number" min="1" wire:model="discountValue" />
                <x-input-error :messages="$errors->get('discountValue')" />
            </div>
            <div>
                <x-input-label value="Usage limit (blank = unlimited)" />
                <x-text-input type="number" min="1" wire:model="usageLimit" />
            </div>
            <div>
                <x-input-label value="Valid from" />
                <x-text-input type="date" wire:model="validFrom" />
                <x-input-error :messages="$errors->get('validFrom')" />
            </div>
            <div>
                <x-input-label value="Valid until" />
                <x-text-input type="date" wire:model="validUntil" />
                <x-input-error :messages="$errors->get('validUntil')" />
            </div>
            <div class="col-span-full flex gap-3">
                <x-primary-button class="w-auto">Save</x-primary-button>
                <button type="button" wire:click="$set('showForm', false)" class="text-sm text-slate-500 hover:text-slate-700">Cancel</button>
            </div>
        </form>
    @endif

    <div class="overflow-hidden rounded-xl border border-slate-200/70 bg-white shadow-sm shadow-slate-900/5">
        <table class="min-w-full divide-y divide-slate-200 text-sm">
            <thead class="bg-slate-50 text-left text-xs font-medium uppercase tracking-wide text-slate-500">
                <tr>
                    <th class="px-4 py-3">Code</th>
                    <th class="px-4 py-3">Discount</th>
                    <th class="px-4 py-3">Scope</th>
                    <th class="px-4 py-3">Validity</th>
                    <th class="px-4 py-3">Used</th>
                    <th class="px-4 py-3"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse ($this->coupons as $coupon)
                    <tr wire:key="coupon-{{ $coupon->id }}">
                        <td class="px-4 py-2">
                            <p class="font-medium text-slate-800">{{ $coupon->code }}</p>
                            <p class="text-xs text-slate-500">{{ $coupon->name }}</p>
                        </td>
                        <td class="px-4 py-2 text-slate-600">
                            {{ $coupon->discount_type->value === 'percent' ? $coupon->discount_value . '%' : '$' . number_format($coupon->discount_value / 100, 2) }}
                        </td>
                        <td class="px-4 py-2 text-slate-600">{{ ucfirst($coupon->scope->value) }}</td>
                        <td class="px-4 py-2 text-slate-600">{{ $coupon->valid_from->format('M j') }} – {{ $coupon->valid_until->format('M j, Y') }}</td>
                        <td class="px-4 py-2 text-slate-600">{{ $coupon->timesUsed() }}{{ $coupon->usage_limit ? '/' . $coupon->usage_limit : '' }}</td>
                        <td class="px-4 py-2 text-right">
                            @can('crm.manage')
                                <button wire:click="toggleActive({{ $coupon->id }})" class="text-xs {{ $coupon->is_active ? 'text-emerald-600' : 'text-slate-400' }}">
                                    {{ $coupon->is_active ? 'Active' : 'Inactive' }}
                                </button>
                            @endcan
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="px-4 py-6 text-center text-slate-500">No coupons yet.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
