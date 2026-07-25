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
                        <button wire:click="startTableOrder({{ $table->id }})" @disabled($table->status->value !== 'free')
                            @class([
                                'rounded-md border p-3 text-center text-sm',
                                'border-emerald-200 bg-emerald-50 text-emerald-700 hover:bg-emerald-100' => $table->status->value === 'free',
                                'border-slate-200 bg-slate-100 text-slate-400' => $table->status->value !== 'free',
                            ])>
                            {{ $table->label }}
                        </button>
                    @endforeach
                </div>
            </div>

            <div class="rounded-xl border border-slate-200/70 bg-white shadow-sm shadow-slate-900/5 p-5">
                <h2 class="mb-3 font-medium text-slate-800">Room service</h2>
                <input type="search" wire:model.live.debounce.300ms="guestSearch" placeholder="Search guest by last name&hellip;"
                    class="mb-3 block w-full rounded-md border-slate-300 text-sm shadow-sm">
                <div class="space-y-1">
                    @foreach ($this->guestResults as $guest)
                        <button wire:click="startRoomServiceOrder({{ $guest->id }})" class="block w-full rounded-md px-3 py-2 text-left text-sm hover:bg-slate-50">
                            {{ $guest->fullName() }}
                        </button>
                    @endforeach
                </div>
            </div>
        </div>
    @else
        @php $order = $this->activeOrder; @endphp
        <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
            <div class="lg:col-span-2 rounded-xl border border-slate-200/70 bg-white shadow-sm shadow-slate-900/5 p-5">
                <h2 class="mb-3 font-medium text-slate-800">Menu</h2>
                @foreach ($this->menu as $categoryName => $items)
                    <p class="mb-1 mt-3 text-xs font-semibold uppercase text-slate-400">{{ $categoryName }}</p>
                    <div class="grid grid-cols-2 gap-2 sm:grid-cols-3">
                        @foreach ($items as $item)
                            <button wire:click="addItem({{ $item->id }})" class="rounded-md border border-slate-200 p-2 text-left text-sm hover:border-brand-300">
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
                    <button wire:click="$set('activeOrderId', null)" class="text-sm text-slate-500 hover:text-slate-700">
                        Back to floor
                    </button>
                </div>
            </div>
        </div>
    @endif
</div>
