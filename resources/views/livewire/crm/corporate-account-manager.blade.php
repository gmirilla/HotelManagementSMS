<div>
    <div class="mb-6 flex flex-wrap items-center justify-between gap-3">
        <h1 class="text-xl font-semibold text-slate-900">Corporate Accounts &amp; Travel Agents</h1>

        @can('crm.manage')
            <button wire:click="create" class="rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500">
                New account
            </button>
        @endcan
    </div>

    @if ($showForm)
        <form wire:submit="save" class="mb-6 grid grid-cols-1 gap-4 rounded-lg border border-slate-200 bg-white p-6 sm:grid-cols-3">
            <div>
                <x-input-label value="Company name" />
                <x-text-input type="text" wire:model="companyName" />
                <x-input-error :messages="$errors->get('companyName')" />
            </div>
            <div>
                <x-input-label value="Account type" />
                <select wire:model="accountType" class="mt-1 block w-full rounded-md border-slate-300 text-sm shadow-sm">
                    @foreach ($accountTypes as $type)
                        <option value="{{ $type->value }}">{{ ucfirst(str_replace('_', ' ', $type->value)) }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <x-input-label value="Billing email" />
                <x-text-input type="email" wire:model="billingEmail" />
                <x-input-error :messages="$errors->get('billingEmail')" />
            </div>
            <div>
                <x-input-label value="Negotiated room rate" />
                <x-text-input type="number" step="0.01" min="0" wire:model="negotiatedRate" />
                <x-input-error :messages="$errors->get('negotiatedRate')" />
            </div>
            <div>
                <x-input-label value="Commission (%)" />
                <x-text-input type="number" step="0.01" min="0" max="100" wire:model="commissionPercent" />
                <x-input-error :messages="$errors->get('commissionPercent')" />
            </div>
            <div class="flex items-end">
                <label class="inline-flex items-center gap-2 text-sm text-slate-700">
                    <input type="checkbox" wire:model="directBillingEnabled" class="rounded border-slate-300">
                    Direct billing enabled
                </label>
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
                <tr>
                    <th class="px-4 py-3">Company</th>
                    <th class="px-4 py-3">Type</th>
                    <th class="px-4 py-3">Rate / Commission</th>
                    <th class="px-4 py-3">Direct billing</th>
                    <th class="px-4 py-3"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse ($this->accounts as $account)
                    <tr wire:key="corp-{{ $account->id }}">
                        <td class="px-4 py-2">
                            <p class="font-medium text-slate-800">{{ $account->company_name }}</p>
                            <p class="text-xs text-slate-500">{{ $account->billing_email }}</p>
                        </td>
                        <td class="px-4 py-2 text-slate-600">{{ ucfirst(str_replace('_', ' ', $account->account_type->value)) }}</td>
                        <td class="px-4 py-2 text-slate-600">
                            @if ($account->account_type->value === 'travel_agent')
                                {{ $account->commission_percent }}% commission
                            @elseif ($account->negotiated_rate_cents)
                                ${{ number_format($account->negotiated_rate_cents / 100, 2) }}/night
                            @else
                                —
                            @endif
                        </td>
                        <td class="px-4 py-2 text-slate-600">{{ $account->direct_billing_enabled ? 'Yes' : 'No' }}</td>
                        <td class="px-4 py-2 text-right">
                            @can('crm.manage')
                                <button wire:click="edit({{ $account->id }})" class="text-xs font-medium text-indigo-600 hover:text-indigo-500">Edit</button>
                            @endcan
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="px-4 py-6 text-center text-slate-500">No corporate accounts yet.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
