<div>
    <div class="mb-6 flex flex-wrap items-center justify-between gap-3">
        <div>
            <h1 class="text-xl font-semibold text-slate-900">Branches</h1>
            <p class="text-sm text-slate-500">Properties within your organization — each has its own rooms, staff, and currency.</p>
        </div>

        <div class="flex items-center gap-3">
            <x-text-input type="search" wire:model.live.debounce.300ms="search" placeholder="Search branches…" class="mt-0 w-56" />
            <button wire:click="create" class="rounded-md bg-brand-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-brand-500">
                New branch
            </button>
        </div>
    </div>

    @if ($showForm)
        <form wire:submit="save" class="mb-6 grid grid-cols-1 gap-4 rounded-xl border border-slate-200/70 bg-white p-6 shadow-sm shadow-slate-900/5 sm:grid-cols-2">
            <div>
                <x-input-label value="Branch name" />
                <x-text-input type="text" wire:model="name" />
                <x-input-error :messages="$errors->get('name')" />
            </div>
            <div>
                <x-input-label value="Code" />
                <x-text-input type="text" wire:model="code" maxlength="20" class="uppercase" />
                <x-input-error :messages="$errors->get('code')" />
            </div>
            <div>
                <x-input-label value="Currency" />
                <x-text-input type="text" wire:model="currency" maxlength="3" class="uppercase" />
                <x-input-error :messages="$errors->get('currency')" />
            </div>
            <div>
                <x-input-label value="Timezone" />
                <select wire:model="timezone" class="mt-1 block w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-brand-500 focus:ring-brand-500">
                    @foreach (\DateTimeZone::listIdentifiers() as $tz)
                        <option value="{{ $tz }}">{{ $tz }}</option>
                    @endforeach
                </select>
                <x-input-error :messages="$errors->get('timezone')" />
            </div>
            <div class="sm:col-span-2">
                <x-input-label value="Address" />
                <x-text-input type="text" wire:model="addressLine1" />
                <x-input-error :messages="$errors->get('addressLine1')" />
            </div>
            <div>
                <x-input-label value="City" />
                <x-text-input type="text" wire:model="city" />
                <x-input-error :messages="$errors->get('city')" />
            </div>
            <div>
                <x-input-label value="Country" />
                <x-text-input type="text" wire:model="country" />
                <x-input-error :messages="$errors->get('country')" />
            </div>
            <div>
                <x-input-label value="Check-in time" />
                <input type="time" wire:model="checkInTime" class="mt-1 block w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-brand-500 focus:ring-brand-500">
                <x-input-error :messages="$errors->get('checkInTime')" />
            </div>
            <div>
                <x-input-label value="Check-out time" />
                <input type="time" wire:model="checkOutTime" class="mt-1 block w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-brand-500 focus:ring-brand-500">
                <x-input-error :messages="$errors->get('checkOutTime')" />
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
                    <th class="px-4 py-3">Branch</th>
                    <th class="px-4 py-3">Location</th>
                    <th class="px-4 py-3">Currency</th>
                    <th class="px-4 py-3">Check-in / out</th>
                    <th class="px-4 py-3">Status</th>
                    <th class="px-4 py-3"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse ($branches as $branch)
                    <tr wire:key="branch-{{ $branch->id }}">
                        <td class="px-4 py-2">
                            <p class="font-medium text-slate-800">{{ $branch->name }}</p>
                            <p class="text-xs text-slate-500">{{ $branch->code }}</p>
                        </td>
                        <td class="px-4 py-2 text-slate-600">{{ collect([$branch->city, $branch->country])->filter()->join(', ') ?: '—' }}</td>
                        <td class="px-4 py-2 text-slate-600">{{ $branch->currency }}</td>
                        <td class="px-4 py-2 text-slate-600">{{ substr((string) $branch->check_in_time, 0, 5) }} / {{ substr((string) $branch->check_out_time, 0, 5) }}</td>
                        <td class="px-4 py-2">
                            @if ($branch->is_active)
                                <span class="rounded-full bg-emerald-100 px-2 py-0.5 text-xs text-emerald-700">Active</span>
                            @else
                                <span class="rounded-full bg-slate-200 px-2 py-0.5 text-xs text-slate-600">Inactive</span>
                            @endif
                        </td>
                        <td class="px-4 py-2 text-right text-xs">
                            <button wire:click="edit({{ $branch->id }})" class="font-medium text-brand-600 hover:text-brand-500">Edit</button>
                            @if ($branch->is_active)
                                <button wire:click="toggleActive({{ $branch->id }})" wire:confirm="Deactivate this branch? It will be hidden from branch switchers and new bookings." class="ml-3 font-medium text-red-600 hover:text-red-500">Deactivate</button>
                            @else
                                <button wire:click="toggleActive({{ $branch->id }})" class="ml-3 font-medium text-emerald-600 hover:text-emerald-500">Activate</button>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="px-4 py-6 text-center text-slate-500">No branches yet.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">
        {{ $branches->links() }}
    </div>
</div>
