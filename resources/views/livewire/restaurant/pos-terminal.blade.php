<div>
    <div class="mb-6 flex flex-wrap items-center justify-between gap-3">
        <h1 class="text-xl font-semibold text-slate-900">Point of Sale</h1>

        <div class="flex flex-wrap gap-2">
            @foreach ($this->outlets as $outlet)
                <button wire:click="$set('selectedOutletId', {{ $outlet->id }})"
                    @class(['rounded-md px-3 py-1.5 text-sm font-medium', 'bg-brand-600 text-white' => $selectedOutletId === $outlet->id, 'bg-slate-100 text-slate-600' => $selectedOutletId !== $outlet->id])>
                    {{ $outlet->name }}
                </button>
            @endforeach
        </div>
    </div>

    @if (! $activeOrderId)
        <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
            <div class="rounded-xl border border-slate-200/70 bg-white shadow-sm shadow-slate-900/5 p-5">
                <h2 class="mb-3 font-medium text-slate-800">Tables</h2>
                <div class="grid grid-cols-3 gap-2 sm:grid-cols-4">
                    @foreach ($this->tables as $table)
                        <button wire:click="selectTable({{ $table->id }})"
                            @class([
                                'rounded-md border p-3 text-center text-sm',
                                'border-emerald-200 bg-emerald-50 text-emerald-700 hover:bg-emerald-100' => $table->status->value === 'free',
                                'border-amber-200 bg-amber-50 text-amber-700 hover:bg-amber-100' => $table->status->value === 'reserved',
                                'border-brand-200 bg-brand-50 text-brand-700 hover:bg-brand-100' => $table->status->value === 'occupied',
                            ])>
                            {{ $table->label }}
                            @if ($table->status->value === 'reserved')
                                <span class="block text-xs">Reserved</span>
                            @elseif ($table->status->value === 'occupied')
                                <span class="block text-xs">View order</span>
                            @endif
                        </button>
                    @endforeach
                </div>
            </div>

            <div class="rounded-xl border border-slate-200/70 bg-white shadow-sm shadow-slate-900/5 p-5">
                <h2 class="mb-3 font-medium text-slate-800">Room service</h2>
                <form wire:submit="searchGuests" class="mb-3 flex gap-2">
                    <input type="search" wire:model="guestSearch" placeholder="Search guest by name or room number&hellip;"
                        class="block w-full rounded-md border-slate-300 text-sm shadow-sm">
                    <button type="submit" class="shrink-0 rounded-md bg-brand-600 px-3 py-2 text-sm font-medium text-white hover:bg-brand-500">
                        <span wire:loading.remove wire:target="searchGuests">Search</span>
                        <span wire:loading wire:target="searchGuests">Searching&hellip;</span>
                    </button>
                </form>

                @if ($hasSearchedGuests)
                    <p class="mb-2 text-xs text-slate-500" wire:loading.remove wire:target="searchGuests">
                        {{ $this->guestResults->count() }} {{ $this->guestResults->count() === 1 ? 'guest' : 'guests' }} found
                    </p>
                @endif

                <div class="space-y-1" wire:loading.remove wire:target="searchGuests">
                    @forelse ($this->guestResults as $guest)
                        @php $room = $guest->reservations->first()?->rooms->first()?->room; @endphp
                        <button wire:click="startRoomServiceOrder({{ $guest->id }})" class="block w-full rounded-md px-3 py-2 text-left text-sm hover:bg-slate-50">
                            {{ $guest->fullName() }}
                            @if ($room)
                                <span class="text-slate-400">— Room {{ $room->room_number }}</span>
                            @endif
                        </button>
                    @empty
                        @if ($hasSearchedGuests)
                            <p class="text-sm text-slate-500">No guests match &ldquo;{{ $guestSearch }}&rdquo;.</p>
                        @endif
                    @endforelse
                </div>
            </div>
        </div>
    @else
        @php $order = $this->activeOrder; @endphp
        <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
            <div class="lg:col-span-2 rounded-xl border border-slate-200/70 bg-white shadow-sm shadow-slate-900/5 p-5">
                <h2 class="mb-3 font-medium text-slate-800">Menu</h2>
                @if ($order->status->value !== 'open')
                    <p class="mb-3 text-xs text-slate-500">This order has been sent to the kitchen — items can no longer be added.</p>
                @endif
                @foreach ($this->menu as $categoryName => $items)
                    <p class="mb-1 mt-3 text-xs font-semibold uppercase text-slate-400">{{ $categoryName }}</p>
                    <div class="grid grid-cols-2 gap-2 sm:grid-cols-3">
                        @foreach ($items as $item)
                            <button wire:click="addItem({{ $item->id }})" @disabled($order->status->value !== 'open')
                                @class([
                                    'rounded-md border p-2 text-left text-sm',
                                    'border-slate-200 hover:border-brand-300' => $order->status->value === 'open',
                                    'border-slate-100 text-slate-400' => $order->status->value !== 'open',
                                ])>
                                <span class="block font-medium text-slate-700">{{ $item->name }}</span>
                                <span class="text-slate-500">₦{{ number_format($item->price_cents / 100, 2) }}</span>
                            </button>
                        @endforeach
                    </div>
                @endforeach
            </div>

            <div class="rounded-xl border border-slate-200/70 bg-white shadow-sm shadow-slate-900/5 p-5">
                <h2 class="mb-1 font-medium text-slate-800">
                    Order — {{ $order->table ? 'Table '.$order->table->label : $order->guest?->fullName() }}
                </h2>
                <p class="mb-3 text-xs text-slate-400">{{ ucfirst(str_replace('_', ' ', $order->status->value)) }}</p>

                <div class="mb-3 divide-y divide-slate-100 text-sm">
                    @forelse ($order->items as $item)
                        <div class="flex justify-between py-1.5">
                            <span>{{ $item->quantity }}&times; {{ $item->menuItem->name }}</span>
                            <span>₦{{ number_format($item->lineTotalCents() / 100, 2) }}</span>
                        </div>
                    @empty
                        <p class="py-2 text-slate-500">No items yet.</p>
                    @endforelse
                </div>

                <div class="border-t border-slate-100 pt-2 text-sm">
                    <div class="flex justify-between text-slate-500"><span>Tax</span><span>₦{{ number_format($order->tax_cents / 100, 2) }}</span></div>
                    <div class="flex justify-between font-semibold text-slate-800"><span>Total</span><span>₦{{ number_format($order->total_cents / 100, 2) }}</span></div>
                </div>

                <div class="mt-4 flex flex-col gap-2">
                    @if ($order->status->value === 'open')
                        <button wire:click="sendToKitchen" class="rounded-md bg-brand-600 px-3 py-2 text-sm font-medium text-white hover:bg-brand-500">
                            Send to kitchen
                        </button>
                    @endif
                    <button wire:click="closeOrder" class="rounded-md bg-slate-800 px-3 py-2 text-sm font-medium text-white hover:bg-slate-700">
                        Close &amp; settle
                    </button>

                    @if ($showVoidForm)
                        <form wire:submit="voidOrder" class="space-y-2 rounded-md border border-red-200 bg-red-50 p-3">
                            <x-input-label value="Reason for voiding" />
                            <x-text-input type="text" wire:model="voidReason" placeholder="e.g. duplicate order, guest walked out" />
                            <x-input-error :messages="$errors->get('reason')" />
                            <div class="flex gap-2">
                                <button type="submit" class="rounded-md bg-red-600 px-3 py-1.5 text-sm font-medium text-white hover:bg-red-500">
                                    Confirm void
                                </button>
                                <button type="button" wire:click="$set('showVoidForm', false)" class="text-sm text-slate-500 hover:text-slate-700">
                                    Cancel
                                </button>
                            </div>
                        </form>
                    @else
                        <button wire:click="$set('showVoidForm', true)" class="rounded-md border border-red-200 px-3 py-2 text-sm font-medium text-red-600 hover:bg-red-50">
                            Void order
                        </button>
                    @endif

                    <button wire:click="$set('activeOrderId', null)" class="text-sm text-slate-500 hover:text-slate-700">
                        Back to floor
                    </button>
                </div>
            </div>
        </div>
    @endif
</div>
