<div>
    <div class="mb-6 flex flex-wrap items-center justify-between gap-3">
        <div>
            <h1 class="text-xl font-semibold text-slate-900">Front Desk</h1>
            @if ($this->activeBranch)
                <p class="text-sm text-slate-500">{{ $this->activeBranch->name }} &middot; {{ now()->format('l, F j, Y') }}</p>
            @endif
        </div>

        <div class="flex items-center gap-3">
            @if ($this->accessibleBranches->count() > 1)
                <select wire:model.live="branchId" class="rounded-md border-slate-300 text-sm shadow-sm focus:border-brand-500 focus:ring-brand-500">
                    @foreach ($this->accessibleBranches as $branch)
                        <option value="{{ $branch->id }}">{{ $branch->name }}</option>
                    @endforeach
                </select>
            @endif

            @can('create', \App\Models\Reservation::class)
                <a href="{{ route('front-desk.walk-in') }}" wire:navigate class="rounded-md bg-brand-600 px-3 py-1.5 text-sm font-medium text-white hover:bg-brand-500">
                    Walk-in check-in
                </a>
            @endcan
        </div>
    </div>

    @if ($lastCheckedOutFolioId)
        <div class="mb-4 flex items-center justify-between rounded-md bg-emerald-50 px-4 py-3 text-sm text-emerald-800">
            <span>Checkout complete.</span>
            <div class="flex items-center gap-3">
                <a href="{{ route('folios.show', $lastCheckedOutFolioId) }}" class="font-medium underline">View folio &amp; receipt</a>
                <button wire:click="$set('lastCheckedOutFolioId', null)" class="text-emerald-700">&times;</button>
            </div>
        </div>
    @endif

    <div class="mb-6 flex gap-1 rounded-lg bg-slate-100 p-1 text-sm font-medium">
        <button wire:click="$set('tab', 'arrivals')" @class(['flex-1 rounded-md py-2', 'bg-white shadow-sm text-slate-900' => $tab === 'arrivals', 'text-slate-500' => $tab !== 'arrivals'])>
            Arrivals ({{ $this->arrivals->count() }})
        </button>
        <button wire:click="$set('tab', 'departures')" @class(['flex-1 rounded-md py-2', 'bg-white shadow-sm text-slate-900' => $tab === 'departures', 'text-slate-500' => $tab !== 'departures'])>
            Departures ({{ $this->departures->count() }})
        </button>
        <button wire:click="$set('tab', 'in_house')" @class(['flex-1 rounded-md py-2', 'bg-white shadow-sm text-slate-900' => $tab === 'in_house', 'text-slate-500' => $tab !== 'in_house'])>
            In-house ({{ $this->inHouse->count() }})
        </button>
    </div>

    @if ($tab === 'arrivals')
        <div class="space-y-3">
            @forelse ($this->arrivals as $reservation)
                <div class="rounded-xl border border-slate-200/70 bg-white shadow-sm shadow-slate-900/5 p-4" wire:key="arrival-{{ $reservation->id }}">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="font-medium text-slate-800">{{ $reservation->guest->fullName() }}</p>
                            <p class="text-sm text-slate-500">{{ $reservation->confirmation_code }} &middot; {{ $reservation->rooms->first()?->roomType?->name }}</p>
                        </div>
                        @can('update', $reservation)
                            <button wire:click="startCheckIn({{ $reservation->id }})" class="rounded-md bg-brand-600 px-3 py-1.5 text-sm font-medium text-white hover:bg-brand-500">
                                Check in
                            </button>
                        @endcan
                    </div>

                    @if ($checkingInReservationId === $reservation->id)
                        @php $bookedRoomTypeId = $reservation->rooms->first()?->room_type_id; @endphp
                        <div class="mt-4 border-t border-slate-100 pt-4">
                            <x-input-label value="Assign room" />
                            <p class="mt-1 text-xs text-slate-500">Picking a room of a different type than booked upgrades or changes the reservation.</p>
                            <div class="mt-2 flex flex-wrap gap-2">
                                @forelse ($this->availableRoomsForCheckIn as $room)
                                    <label @class([
                                        'cursor-pointer rounded-md border px-3 py-1.5 text-sm transition focus-within:ring-2 focus-within:ring-brand-500 focus-within:ring-offset-1',
                                        'border-brand-500 bg-brand-50 text-brand-700 ring-1 ring-brand-500/30' => $selectedRoomId === $room->id,
                                        'border-slate-200 text-slate-600 hover:border-brand-300 hover:bg-brand-50/60 hover:text-brand-700' => $selectedRoomId !== $room->id,
                                    ])>
                                        <input type="radio" wire:model="selectedRoomId" value="{{ $room->id }}" class="sr-only">
                                        {{ $room->room_number }} &middot; {{ $room->roomType->name }}
                                        @if ($room->room_type_id !== $bookedRoomTypeId)
                                            <span class="text-brand-600">(change)</span>
                                        @endif
                                    </label>
                                @empty
                                    <p class="text-sm text-slate-500">No vacant rooms at this branch right now.</p>
                                @endforelse
                            </div>
                            <x-input-error :messages="$errors->get('selectedRoomId')" />

                            @if ($reservation->branch && now()->format('H:i:s') < $reservation->branch->check_in_time->format('H:i:s'))
                                <div class="mt-3 rounded-md bg-amber-50 px-3 py-2 text-xs text-amber-800">
                                    This is before the standard check-in time ({{ $reservation->branch->check_in_time->format('g:i A') }}).
                                    <div class="mt-2">
                                        <x-input-label for="earlyCheckInFee-{{ $reservation->id }}" value="Early check-in fee (optional)" class="text-xs" />
                                        <x-text-input id="earlyCheckInFee-{{ $reservation->id }}" type="number" step="0.01" wire:model="earlyCheckInFee" placeholder="0.00" class="mt-1 w-32" />
                                        <x-input-error :messages="$errors->get('earlyCheckInFee')" />
                                    </div>
                                </div>
                            @endif

                            <div class="mt-3 flex gap-3">
                                <button wire:click="completeCheckIn" class="rounded-md bg-brand-600 px-4 py-2 text-sm font-semibold text-white hover:bg-brand-500">
                                    Confirm check-in
                                </button>
                                <button wire:click="$set('checkingInReservationId', null)" class="text-sm text-slate-500 hover:text-slate-700">Cancel</button>
                            </div>
                        </div>
                    @endif
                </div>
            @empty
                <p class="text-sm text-slate-500">No arrivals scheduled for today.</p>
            @endforelse
        </div>
    @endif

    @if ($tab === 'departures')
        <div class="space-y-3">
            @forelse ($this->departures as $reservation)
                <div class="rounded-xl border border-slate-200/70 bg-white shadow-sm shadow-slate-900/5 p-4" wire:key="departure-{{ $reservation->id }}">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="font-medium text-slate-800">{{ $reservation->guest->fullName() }}</p>
                            <p class="text-sm text-slate-500">
                                Room {{ $reservation->rooms->first()?->room?->room_number }}
                                @if ($reservation->folio)
                                    &middot; Balance
                                    <a href="{{ route('folios.show', $reservation->folio) }}" class="font-medium text-brand-600 hover:text-brand-500">
                                        ₦{{ number_format($reservation->folio->balance_cents / 100, 2) }}
                                    </a>
                                @endif
                            </p>
                        </div>
                        <div class="flex items-center gap-2">
                            @if ($reservation->folio && $reservation->folio->balance_cents > 0)
                                @can('update', $reservation->folio)
                                    <button wire:click="startPayment({{ $reservation->id }})" class="rounded-md border border-slate-200 px-3 py-1.5 text-sm font-medium text-slate-700 hover:bg-slate-50">
                                        Add payment
                                    </button>
                                @endcan
                            @endif
                            @if ($reservation->folio && $reservation->folio->isOpen() && $reservation->branch && now()->format('H:i:s') > $reservation->branch->check_out_time->format('H:i:s'))
                                @can('update', $reservation->folio)
                                    <button wire:click="startLateFee({{ $reservation->id }})" class="rounded-md border border-amber-200 px-3 py-1.5 text-sm font-medium text-amber-700 hover:bg-amber-50">
                                        Add late fee
                                    </button>
                                @endcan
                            @endif
                            @can('update', $reservation)
                                <button wire:click="checkOut({{ $reservation->id }})" wire:confirm="Check out this guest?" class="rounded-md bg-slate-800 px-3 py-1.5 text-sm font-medium text-white hover:bg-slate-700">
                                    Check out
                                </button>
                            @endcan
                        </div>
                    </div>

                    @if ($checkoutErrorReservationId === $reservation->id)
                        <div class="mt-3 rounded-md bg-red-50 px-3 py-2 text-sm text-red-800">
                            {{ $checkoutError }}
                            @can('forceCheckout', $reservation->folio)
                                <button wire:click="startForceCheckout({{ $reservation->id }})" class="ml-2 font-medium underline">Force checkout</button>
                            @endcan
                        </div>
                    @endif

                    @if ($forceCheckoutReservationId === $reservation->id)
                        <div class="mt-3 rounded-md border border-red-200 bg-red-50 p-3">
                            <x-input-label value="Reason for overriding the outstanding balance" class="text-xs" />
                            <textarea wire:model="forceCheckoutReason" rows="2" class="mt-1 block w-full rounded-md border-slate-300 text-sm shadow-sm focus:border-red-500 focus:ring-red-500"></textarea>
                            <x-input-error :messages="$errors->get('forceCheckoutReason')" />
                            <div class="mt-2 flex gap-3">
                                <button wire:click="confirmForceCheckout" wire:confirm="Check out this guest despite the outstanding balance?" class="rounded-md bg-red-600 px-3 py-1.5 text-sm font-medium text-white hover:bg-red-500">
                                    Confirm override
                                </button>
                                <button wire:click="cancelForceCheckout" class="text-sm text-slate-500 hover:text-slate-700">Cancel</button>
                            </div>
                        </div>
                    @endif

                    @if ($payingReservationId === $reservation->id)
                        <div class="mt-3 flex flex-wrap items-end gap-2 rounded-md bg-slate-50 p-3">
                            <div>
                                <x-input-label value="Method" class="text-xs" />
                                <select wire:model="paymentMethod" class="mt-1 rounded-md border-slate-300 text-sm shadow-sm">
                                    @foreach ($paymentMethods as $method)
                                        <option value="{{ $method->value }}">{{ ucfirst(str_replace('_', ' ', $method->value)) }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div>
                                <x-input-label value="Amount" class="text-xs" />
                                <x-text-input type="number" step="0.01" wire:model="paymentAmount" placeholder="0.00" class="mt-1 w-28" />
                                <x-input-error :messages="$errors->get('paymentAmount')" />
                            </div>
                            <button wire:click="recordPayment" class="rounded-md bg-brand-600 px-3 py-2 text-sm font-semibold text-white hover:bg-brand-500">
                                Record payment
                            </button>
                            <button wire:click="cancelPayment" class="text-sm text-slate-500 hover:text-slate-700">Cancel</button>
                        </div>
                    @endif

                    @if ($addingLateFeeReservationId === $reservation->id)
                        <div class="mt-3 flex flex-wrap items-end gap-2 rounded-md bg-amber-50 p-3">
                            <div>
                                <x-input-label value="Late checkout fee amount" class="text-xs" />
                                <x-text-input type="number" step="0.01" wire:model="lateFeeAmount" placeholder="0.00" class="mt-1 w-28" />
                                <x-input-error :messages="$errors->get('lateFeeAmount')" />
                            </div>
                            <button wire:click="confirmLateFee" class="rounded-md bg-amber-600 px-3 py-2 text-sm font-semibold text-white hover:bg-amber-500">
                                Add fee
                            </button>
                            <button wire:click="cancelLateFee" class="text-sm text-slate-500 hover:text-slate-700">Cancel</button>
                        </div>
                    @endif
                </div>
            @empty
                <p class="text-sm text-slate-500">No departures due.</p>
            @endforelse
        </div>
    @endif

    @if ($tab === 'in_house')
        <div class="space-y-3">
            @forelse ($this->inHouse as $reservation)
                <div class="rounded-xl border border-slate-200/70 bg-white shadow-sm shadow-slate-900/5 p-4" wire:key="inhouse-{{ $reservation->id }}">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="font-medium text-slate-800">{{ $reservation->guest->fullName() }}</p>
                            <p class="text-sm text-slate-500">
                                Room {{ $reservation->rooms->first()?->room?->room_number }} &middot; {{ $reservation->rooms->first()?->room?->roomType?->name }}
                                &middot; until {{ $reservation->departure_date->format('M j, Y') }}
                                @if ($reservation->folio)
                                    &middot; Balance
                                    <a href="{{ route('folios.show', $reservation->folio) }}" class="font-medium text-brand-600 hover:text-brand-500">
                                        ₦{{ number_format($reservation->folio->balance_cents / 100, 2) }}
                                    </a>
                                @endif
                            </p>
                        </div>
                        <div class="flex items-center gap-3">
                            @if ($reservation->folio)
                                <a href="{{ route('folios.show', $reservation->folio) }}" class="rounded-md border border-slate-200 px-3 py-1.5 text-sm font-medium text-slate-700 hover:bg-slate-50">
                                    View folio
                                </a>
                            @endif
                            @can('update', $reservation)
                                <button wire:click="startRoomChange({{ $reservation->id }})" class="rounded-md border border-slate-200 px-3 py-1.5 text-sm font-medium text-slate-700 hover:bg-slate-50">
                                    Change room
                                </button>
                            @endcan
                        </div>
                    </div>

                    @if ($changingRoomReservationId === $reservation->id)
                        @php $currentRoomTypeId = $reservation->rooms->first()?->room?->room_type_id; @endphp
                        <div class="mt-4 border-t border-slate-100 pt-4">
                            <x-input-label value="Move to room" />
                            <p class="mt-1 text-xs text-slate-500">Moving to a different room type upgrades or downgrades the reservation and reprices the remaining nights.</p>
                            <div class="mt-2 flex flex-wrap gap-2">
                                @forelse ($this->availableRoomsForRoomChange as $room)
                                    <label @class([
                                        'cursor-pointer rounded-md border px-3 py-1.5 text-sm transition focus-within:ring-2 focus-within:ring-brand-500 focus-within:ring-offset-1',
                                        'border-brand-500 bg-brand-50 text-brand-700 ring-1 ring-brand-500/30' => $selectedNewRoomId === $room->id,
                                        'border-slate-200 text-slate-600 hover:border-brand-300 hover:bg-brand-50/60 hover:text-brand-700' => $selectedNewRoomId !== $room->id,
                                    ])>
                                        <input type="radio" wire:model="selectedNewRoomId" value="{{ $room->id }}" class="sr-only">
                                        {{ $room->room_number }} &middot; {{ $room->roomType->name }}
                                        @if ($room->room_type_id !== $currentRoomTypeId)
                                            <span class="text-brand-600">(change)</span>
                                        @endif
                                    </label>
                                @empty
                                    <p class="text-sm text-slate-500">No other vacant rooms at this branch right now.</p>
                                @endforelse
                            </div>
                            <x-input-error :messages="$errors->get('selectedNewRoomId')" />

                            <div class="mt-3">
                                <x-input-label for="roomChangeReason-{{ $reservation->id }}" value="Reason (optional)" />
                                <x-text-input id="roomChangeReason-{{ $reservation->id }}" type="text" wire:model="roomChangeReason" placeholder="e.g. Complimentary upgrade, maintenance issue…" />
                            </div>

                            <div class="mt-3 flex gap-3">
                                <button wire:click="completeRoomChange" class="rounded-md bg-brand-600 px-4 py-2 text-sm font-semibold text-white hover:bg-brand-500">
                                    Confirm room change
                                </button>
                                <button wire:click="cancelRoomChange" class="text-sm text-slate-500 hover:text-slate-700">Cancel</button>
                            </div>
                        </div>
                    @endif
                </div>
            @empty
                <p class="text-sm text-slate-500">No guests currently in-house.</p>
            @endforelse
        </div>
    @endif
</div>
