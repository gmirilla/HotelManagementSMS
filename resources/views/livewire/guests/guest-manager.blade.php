<div>
    <div class="mb-6 flex flex-wrap items-center justify-between gap-3">
        <h1 class="text-xl font-semibold text-slate-900">Guests</h1>

        <div class="flex items-center gap-3">
            <input type="search" wire:model.live.debounce.300ms="search" placeholder="Search name or email&hellip;"
                class="rounded-md border-slate-300 text-sm shadow-sm focus:border-brand-500 focus:ring-brand-500">

            @can('create', App\Models\Guest::class)
                <button wire:click="create" class="rounded-md bg-brand-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-brand-500">
                    New guest
                </button>
            @endcan
        </div>
    </div>

    @if ($showForm)
        <div class="mb-6 rounded-xl border border-slate-200/70 bg-white shadow-sm shadow-slate-900/5 p-6">
            <h2 class="mb-4 font-medium text-slate-800">{{ $editingId ? 'Edit guest' : 'New guest' }}</h2>

            <form wire:submit="save" class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <div>
                    <x-input-label for="firstName" value="First name" />
                    <x-text-input id="firstName" type="text" wire:model="firstName" required />
                    <x-input-error :messages="$errors->get('firstName')" />
                </div>

                <div>
                    <x-input-label for="lastName" value="Last name" />
                    <x-text-input id="lastName" type="text" wire:model="lastName" required />
                    <x-input-error :messages="$errors->get('lastName')" />
                </div>

                <div>
                    <x-input-label for="email" value="Email" />
                    <x-text-input id="email" type="email" wire:model="email" />
                    <x-input-error :messages="$errors->get('email')" />
                </div>

                <div>
                    <x-input-label for="phone" value="Phone" />
                    <x-text-input id="phone" type="text" wire:model="phone" />
                </div>

                <div>
                    <x-input-label for="nationality" value="Nationality" />
                    <x-text-input id="nationality" type="text" wire:model="nationality" />
                </div>

                <div class="col-span-full flex gap-3">
                    <x-primary-button class="w-auto">Save</x-primary-button>
                    <button type="button" wire:click="$set('showForm', false)" class="text-sm text-slate-500 hover:text-slate-700">
                        Cancel
                    </button>
                </div>
            </form>
        </div>
    @endif

    <div class="overflow-hidden rounded-xl border border-slate-200/70 bg-white shadow-sm shadow-slate-900/5">
        <table class="min-w-full divide-y divide-slate-200 text-sm">
            <thead class="bg-slate-50 text-left text-xs font-medium uppercase tracking-wide text-slate-500">
                <tr>
                    <th class="px-4 py-3">Name</th>
                    <th class="px-4 py-3">Email</th>
                    <th class="px-4 py-3">Phone</th>
                    <th class="px-4 py-3">Flag</th>
                    <th class="px-4 py-3"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse ($guests as $guest)
                    <tr wire:key="guest-{{ $guest->id }}">
                        <td class="px-4 py-3">
                            <a href="{{ route('guests.show', $guest) }}" class="font-medium text-brand-600 hover:text-brand-500">
                                {{ $guest->fullName() }}
                            </a>
                        </td>
                        <td class="px-4 py-3 text-slate-600">{{ $guest->email }}</td>
                        <td class="px-4 py-3 text-slate-600">{{ $guest->phone }}</td>
                        <td class="px-4 py-3">
                            @if ($guest->flag->value !== 'none')
                                <span @class([
                                    'rounded-full px-2 py-0.5 text-xs font-medium',
                                    'bg-amber-100 text-amber-800' => $guest->flag->value === 'vip',
                                    'bg-red-100 text-red-800' => $guest->flag->value === 'blacklisted',
                                ])>{{ ucfirst($guest->flag->value) }}</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-right">
                            <div class="flex justify-end gap-3">
                                <button wire:click="toggleVip({{ $guest->id }})" class="text-xs font-medium text-slate-500 hover:text-slate-700">
                                    {{ $guest->flag->value === 'vip' ? 'Unmark VIP' : 'Mark VIP' }}
                                </button>
                                @can('update', $guest)
                                    <button wire:click="edit({{ $guest->id }})" class="text-xs font-medium text-brand-600 hover:text-brand-500">Edit</button>
                                @endcan
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="px-4 py-6 text-center text-slate-500">No guests found.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">{{ $guests->links() }}</div>
</div>
