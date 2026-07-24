<div class="mx-auto max-w-3xl">
    <h1 class="mb-6 text-xl font-semibold text-slate-900">New reservation</h1>

    <div class="mb-6 flex items-center gap-2 text-xs font-medium text-slate-500">
        <span class="{{ $step >= 1 ? 'text-brand-600' : '' }}">1. Dates</span>
        <span>&rarr;</span>
        <span class="{{ $step >= 2 ? 'text-brand-600' : '' }}">2. Room</span>
        <span>&rarr;</span>
        <span class="{{ $step >= 3 ? 'text-brand-600' : '' }}">3. Guest &amp; confirm</span>
    </div>

    @if ($step === 1)
        <div class="rounded-xl border border-slate-200/70 bg-white shadow-sm shadow-slate-900/5 p-6">
            @if ($this->accessibleBranches->count() > 1)
                <div class="mb-4">
                    <x-input-label for="branchId" value="Branch" />
                    <select id="branchId" wire:model="branchId" class="mt-1 block w-full rounded-md border-slate-300 shadow-sm sm:text-sm">
                        @foreach ($this->accessibleBranches as $branch)
                            <option value="{{ $branch->id }}">{{ $branch->name }}</option>
                        @endforeach
                    </select>
                </div>
            @endif

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <x-input-label for="arrivalDate" value="Arrival" />
                    <x-text-input id="arrivalDate" type="date" wire:model="arrivalDate" />
                    <x-input-error :messages="$errors->get('arrivalDate')" />
                </div>
                <div>
                    <x-input-label for="departureDate" value="Departure" />
                    <x-text-input id="departureDate" type="date" wire:model="departureDate" />
                    <x-input-error :messages="$errors->get('departureDate')" />
                </div>
                <div>
                    <x-input-label for="adults" value="Adults" />
                    <x-text-input id="adults" type="number" min="1" wire:model="adults" />
                </div>
                <div>
                    <x-input-label for="children" value="Children" />
                    <x-text-input id="children" type="number" min="0" wire:model="children" />
                </div>
            </div>

            <button wire:click="searchAvailability" class="mt-6 rounded-md bg-brand-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-brand-500">
                Search availability
            </button>
        </div>
    @endif

    @if ($step === 2)
        <div class="space-y-3">
            @forelse ($this->availableRoomTypes as $row)
                <div class="flex items-center justify-between rounded-xl border border-slate-200/70 bg-white shadow-sm shadow-slate-900/5 p-4">
                    <div>
                        <p class="font-medium text-slate-800">{{ $row['roomType']->name }}</p>
                        <p class="text-sm text-slate-500">{{ $row['available'] }} available &middot; up to {{ $row['roomType']->base_capacity_adults }} adults</p>
                    </div>
                    <div class="text-right">
                        <p class="font-semibold text-slate-800">${{ number_format($row['averageRateCents'] / 100, 2) }}/night avg</p>
                        <button wire:click="selectRoomType({{ $row['roomType']->id }})" class="mt-1 text-sm font-medium text-brand-600 hover:text-brand-500">
                            Select &rarr;
                        </button>
                    </div>
                </div>
            @empty
                <p class="text-sm text-slate-500">No room types have availability for these dates.</p>
            @endforelse

            <button wire:click="$set('step', 1)" class="text-sm text-slate-500 hover:text-slate-700">&larr; Change dates</button>
        </div>
    @endif

    @if ($step === 3)
        <div class="rounded-xl border border-slate-200/70 bg-white shadow-sm shadow-slate-900/5 p-6">
            <h2 class="mb-4 font-medium text-slate-800">Guest</h2>

            @if (! $creatingNewGuest)
                <input type="search" wire:model.live.debounce.300ms="guestSearch" placeholder="Search existing guests&hellip;"
                    class="mb-3 block w-full rounded-md border-slate-300 text-sm shadow-sm focus:border-brand-500 focus:ring-brand-500">

                <div class="mb-4 max-h-48 space-y-1 overflow-y-auto">
                    @foreach ($this->guestResults as $guest)
                        <button type="button" wire:click="selectGuest({{ $guest->id }})"
                            @class(['block w-full rounded-md px-3 py-2 text-left text-sm hover:bg-slate-50', 'bg-brand-50' => $selectedGuestId === $guest->id])>
                            {{ $guest->fullName() }} <span class="text-slate-400">{{ $guest->email }}</span>
                        </button>
                    @endforeach
                </div>

                <button type="button" wire:click="$set('creatingNewGuest', true)" class="text-sm font-medium text-brand-600 hover:text-brand-500">
                    + New guest instead
                </button>
            @else
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <x-input-label for="newGuestFirstName" value="First name" />
                        <x-text-input id="newGuestFirstName" type="text" wire:model="newGuestFirstName" />
                        <x-input-error :messages="$errors->get('newGuestFirstName')" />
                    </div>
                    <div>
                        <x-input-label for="newGuestLastName" value="Last name" />
                        <x-text-input id="newGuestLastName" type="text" wire:model="newGuestLastName" />
                        <x-input-error :messages="$errors->get('newGuestLastName')" />
                    </div>
                    <div class="col-span-2">
                        <x-input-label for="newGuestEmail" value="Email" />
                        <x-text-input id="newGuestEmail" type="email" wire:model="newGuestEmail" />
                    </div>
                </div>
                <button type="button" wire:click="$set('creatingNewGuest', false)" class="mt-2 text-sm text-slate-500 hover:text-slate-700">
                    &larr; Search existing guests instead
                </button>
            @endif

            <div class="mt-4">
                <x-input-label for="specialRequests" value="Special requests" />
                <textarea id="specialRequests" wire:model="specialRequests" rows="2"
                    class="mt-1 block w-full rounded-md border-slate-300 shadow-sm focus:border-brand-500 focus:ring-brand-500 sm:text-sm"></textarea>
            </div>

            <div class="mt-6 flex gap-3">
                <button wire:click="confirm" class="rounded-md bg-brand-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-brand-500">
                    Confirm reservation
                </button>
                <button wire:click="$set('step', 2)" class="text-sm text-slate-500 hover:text-slate-700">&larr; Back</button>
            </div>
        </div>
    @endif
</div>
